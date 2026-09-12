<?php

namespace App\Jobs;

use App\Models\RefundRequest;
use App\Services\Refund\SslCommerzRefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessRefund implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;
    public bool $failOnTimeout = true;
    public int $uniqueFor = 3600;

    public function __construct(public readonly int $refundId)
    {
        $this->onQueue('refunds');
    }

    public function uniqueId(): string
    {
        return "refund:{$this->refundId}";
    }

    /**
     * @throws Throwable
     */
    public function handle(SslCommerzRefundService $gateway): void
    {
        $refund = DB::transaction(function (): ?RefundRequest {
            $locked = RefundRequest::query()
                ->lockForUpdate()
                ->find($this->refundId);

            if (!$locked || $locked->status !== RefundRequest::STATUS_APPROVED) {
                return null;
            }

            if ($locked->gateway_refund_id) {
                return null;
            }

            $locked->update([
                'status' => RefundRequest::STATUS_PROCESSING,
                'gateway_status' => 'processing',
                'processed_at' => now(),
                'failure_reason' => null,
            ]);

            return $locked->fresh('transaction');
        });

        if (!$refund) {
            return;
        }

        try {
            $result = $gateway->initiate($refund);

            DB::transaction(function () use ($result): void {
                $locked = RefundRequest::query()
                    ->with(['booking', 'transaction'])
                    ->lockForUpdate()
                    ->findOrFail($this->refundId);

                $completed = $result['status'] === 'completed';

                $locked->update([
                    'status' => $completed
                        ? RefundRequest::STATUS_COMPLETED
                        : RefundRequest::STATUS_PROCESSING,
                    'gateway_refund_id' => $result['refund_ref_id'],
                    'gateway_status' => $result['status'],
                    'gateway_response' => $result['response'],
                    'completed_at' => $completed ? now() : null,
                ]);

                if ($completed) {
                    $this->completeFinancialRecords($locked);
                }
            });
        } catch (Throwable $exception) {
            Log::error('Refund submission failed', [
                'refund_id' => $this->refundId,
                'message' => $exception->getMessage(),
            ]);

            RefundRequest::query()
                ->whereKey($this->refundId)
                ->where('status', RefundRequest::STATUS_PROCESSING)
                ->whereNull('gateway_refund_id')
                ->update([
                    'status' => RefundRequest::STATUS_FAILED,
                    'gateway_status' => 'failed',
                    'failure_reason' => $exception->getMessage(),
                ]);

            throw $exception;
        }
    }

    private function completeFinancialRecords(RefundRequest $refund): void
    {
        $refund->booking->update([
            'status' => 'refunded',
            'payment_status' => 'refunded',
        ]);

        $refund->transaction->update([
            'status' => 'refunded',
        ]);
    }
}
