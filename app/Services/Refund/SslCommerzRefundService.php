<?php

namespace App\Services\Refund;

use App\Models\RefundRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SslCommerzRefundService
{
    /**
     * Submit an approved refund to SSLCommerz.
     */
    public function initiate(RefundRequest $refund): array
    {
        $refund->loadMissing('transaction');

        if (!in_array($refund->status, [
            RefundRequest::STATUS_APPROVED,
            RefundRequest::STATUS_PROCESSING,
        ], true)) {
            throw new RuntimeException('Only an approved refund can be submitted.');
        }

        if ($refund->gateway_refund_id) {
            throw new RuntimeException('This refund has already been submitted to SSLCommerz.');
        }

        if (!$refund->transaction?->transaction_reference) {
            throw new RuntimeException('The original bank transaction ID is missing.');
        }

        if ((float) $refund->approved_amount <= 0) {
            throw new RuntimeException('The approved refund amount must be greater than zero.');
        }

        $transactionReference = $refund->transaction->transaction_reference;
        $isLocalTestTransaction = str_starts_with(
            $transactionReference,
            'LOCAL-TEST-'
        );

        // Never send a locally generated bank reference to SSLCommerz.
        if ($isLocalTestTransaction && !$this->shouldSimulate()) {
            throw new RuntimeException(
                'LOCAL-TEST transaction detected, but local refund simulation is disabled.'
            );
        }

        if ($isLocalTestTransaction || $this->shouldSimulate()) {
            return $this->simulatedRefund($refund);
        }

        $data = $this->request('refund_payment', [
            'bank_tran_id' => $transactionReference,
            // Mandatory in SSLCommerz Refund API since 24 February 2025.
            // Must be unique and no longer than 30 characters.
            'refund_trans_id' => $this->gatewayTransactionId($refund),
            'refund_amount' => number_format((float) $refund->approved_amount, 2, '.', ''),
            'refund_remarks' => $this->remarks($refund),
            'refe_id' => substr($refund->refund_number, 0, 50),
        ]);

        $refundReference = $data['refund_ref_id'] ?? null;

        if (($data['APIConnect'] ?? null) !== 'DONE' || !$refundReference) {
            throw new RuntimeException(
                $data['errorReason']
                ?? $data['failedreason']
                ?? 'SSLCommerz did not accept the refund request.'
            );
        }

        return [
            'accepted' => true,
            'refund_ref_id' => (string) $refundReference,
            'status' => $this->normalizeStatus($data['status'] ?? 'processing'),
            'response' => $data,
        ];
    }

    /**
     * Retrieve the current state of a previously submitted refund.
     */
    public function status(string $refundReference): array
    {
        if (trim($refundReference) === '') {
            throw new RuntimeException('The SSLCommerz refund reference is required.');
        }

        $data = $this->request('refund_status', [
            'refund_ref_id' => $refundReference,
        ]);

        if (($data['APIConnect'] ?? null) !== 'DONE') {
            throw new RuntimeException(
                $data['errorReason']
                ?? $data['failedreason']
                ?? 'SSLCommerz could not retrieve the refund status.'
            );
        }

        return [
            'refund_ref_id' => (string) ($data['refund_ref_id'] ?? $refundReference),
            'status' => $this->normalizeStatus($data['status'] ?? null),
            'response' => $data,
        ];
    }

    private function request(string $endpoint, array $parameters): array
    {
        $storeId = config('sslcommerz.apiCredentials.store_id');
        $storePassword = config('sslcommerz.apiCredentials.store_password');

        if (!$storeId || !$storePassword) {
            throw new RuntimeException('SSLCommerz refund credentials are not configured.');
        }

        $url = rtrim((string) config('sslcommerz.apiDomain'), '/')
            . config("sslcommerz.apiUrl.{$endpoint}");

        try {
            $response = $this->client()->get($url, array_merge($parameters, [
                'store_id' => $storeId,
                'store_passwd' => $storePassword,
                'v' => 1,
                'format' => 'json',
            ]));
        } catch (ConnectionException $exception) {
            Log::error('SSLCommerz refund connection failed', [
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Could not connect to SSLCommerz.',
                previous: $exception
            );
        }

        if (!$response->successful()) {
            Log::error('SSLCommerz refund HTTP request failed', [
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                "SSLCommerz refund request returned HTTP {$response->status()}."
            );
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new RuntimeException('SSLCommerz returned an invalid refund response.');
        }

        return $data;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout((int) config('sslcommerz.refund.timeout', 20))
            ->retry(
                (int) config('sslcommerz.refund.retry_times', 2),
                (int) config('sslcommerz.refund.retry_delay_ms', 300),
                throw: false
            );
    }

    private function remarks(RefundRequest $refund): string
    {
        return substr(
            "Oystay refund {$refund->refund_number}",
            0,
            (int) config('sslcommerz.refund.remarks_max_length', 255)
        );
    }

    private function gatewayTransactionId(RefundRequest $refund): string
    {
        $id = str_pad((string) $refund->id, 8, '0', STR_PAD_LEFT);
        $hash = substr(hash('sha256', $refund->idempotency_key), 0, 20);

        return substr("RF{$id}{$hash}", 0, 30);
    }

    private function shouldSimulate(): bool
    {
        return app()->environment('local')
            && config('sslcommerz.refund.fake', false) === true;
    }

    private function simulatedRefund(RefundRequest $refund): array
    {
        $reference = "LOCAL-REFUND-{$refund->id}";

        Log::warning('Using local simulated SSLCommerz refund', [
            'refund_id' => $refund->id,
            'refund_number' => $refund->refund_number,
        ]);

        return [
            'accepted' => true,
            'refund_ref_id' => $reference,
            'status' => 'completed',
            'response' => [
                'APIConnect' => 'DONE',
                'status' => 'refunded',
                'refund_ref_id' => $reference,
                'bank_tran_id' => $refund->transaction->transaction_reference,
                'local_simulation' => true,
            ],
        ];
    }

    private function normalizeStatus(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'refunded', 'completed' => 'completed',
            'failed', 'cancelled', 'canceled', 'declined' => 'failed',
            default => 'processing',
        };
    }
}
