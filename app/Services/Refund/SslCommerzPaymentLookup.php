<?php

namespace App\Services\Refund;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asks SSLCommerz "was this payment really made?" using the guest's transaction id.
 *
 * On a local machine SSLCommerz cannot call back (IPN), so the bank's own reference of a payment is never
 * received — and a refund needs exactly that reference. This fetches it, the same way the callback would
 * have delivered it.
 */
class SslCommerzPaymentLookup
{
    /**
     * The payment as SSLCommerz knows it, or null when no valid payment exists for this id.
     *
     * @return array{tran_id:string, val_id:string, bank_tran_id:string, amount:float, status:string}|null
     */
    public function findPaid(string $tranId): ?array
    {
        $storeId       = config('sslcommerz.apiCredentials.store_id');
        $storePassword = config('sslcommerz.apiCredentials.store_password');

        if (!$storeId || !$storePassword) {
            return null;
        }

        try {
            $response = Http::acceptJson()->timeout(20)->get(
                rtrim((string) config('sslcommerz.apiDomain'), '/') . config('sslcommerz.apiUrl.transaction_status'),
                ['tran_id' => $tranId, 'store_id' => $storeId, 'store_passwd' => $storePassword, 'format' => 'json']
            );

            if (!$response->successful()) {
                return null;
            }

            $elements = $response->json('element') ?? [];
        } catch (\Throwable $e) {
            Log::warning('SSLCommerz payment lookup failed: ' . $e->getMessage());

            return null;
        }

        // A guest may have tried more than once; the one that went through is the one that counts
        foreach ($elements as $element) {
            if (in_array($element['status'] ?? null, ['VALID', 'VALIDATED'], true)
                && filled($element['val_id'] ?? null)
                && filled($element['bank_tran_id'] ?? null)) {
                return [
                    'tran_id'      => (string) $element['tran_id'],
                    'val_id'       => (string) $element['val_id'],
                    'bank_tran_id' => (string) $element['bank_tran_id'],
                    'amount'       => (float) ($element['amount'] ?? 0),
                    'status'       => (string) $element['status'],
                ];
            }
        }

        return null;
    }
}
