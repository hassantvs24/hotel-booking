<?php

// Runs every 5 minutes via the scheduler (needs `php artisan schedule:work`).
//
// SSLCommerz often answers a refund request with "processing" and finishes it later. Nothing tells us
// when it is done, so this asks the gateway about every refund still waiting. When one has finished
// the booking and payment are marked refunded and the guest gets the "refund complete" email; when the
// gateway has refused it the refund is marked failed so the admin can see why.

namespace App\Jobs;

use App\Models\RefundRequest;
use App\Services\Refund\RefundNotificationMailer;
use App\Services\Refund\SslCommerzRefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckRefundStatuses implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(SslCommerzRefundService $gateway): void
    {
        RefundRequest::query()
            ->where('status', RefundRequest::STATUS_PROCESSING)
            ->whereNotNull('gateway_refund_id')
            ->where('gateway_refund_id', 'not like', 'LOCAL-REFUND-%')   // simulated ones are final already
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->each(fn (RefundRequest $refund) => $this->checkOne($gateway, $refund));
    }

    /** Ask SSLCommerz about one refund and act on the answer (also used by the admin's "check now" button) */
    public function checkOne(SslCommerzRefundService $gateway, RefundRequest $refund): void
    {
        try {
            $result = $gateway->status($refund->gateway_refund_id);
        } catch (Throwable $e) {
            // Try again at the next run
            Log::warning('Refund status check failed', ['refund_id' => $refund->id, 'message' => $e->getMessage()]);

            return;
        }

        if ($result['status'] === 'completed') {
            if ($this->settle($refund->id, $result)) {
                RefundNotificationMailer::sendCompleted(RefundRequest::findOrFail($refund->id));
            }

            return;
        }

        if ($result['status'] === 'failed') {
            RefundRequest::query()
                ->whereKey($refund->id)
                ->where('status', RefundRequest::STATUS_PROCESSING)
                ->update([
                    'status'           => RefundRequest::STATUS_FAILED,
                    'gateway_status'   => 'failed',
                    'gateway_response' => $result['response'],
                    'failure_reason'   => $result['response']['errorReason']
                        ?? $result['response']['failedreason']
                        ?? 'SSLCommerz reported that the refund failed.',
                ]);

            return;
        }

        // Still processing — keep the latest answer for the admin to see
        RefundRequest::query()->whereKey($refund->id)->update([
            'gateway_status'   => $result['status'],
            'gateway_response' => $result['response'],
        ]);
    }

    /** Mark it done. Returns false when someone else already did (so the email is sent only once). */
    private function settle(int $refundId, array $result): bool
    {
        return DB::transaction(function () use ($refundId, $result): bool {
            $locked = RefundRequest::query()
                ->with(['booking', 'transaction'])
                ->lockForUpdate()
                ->find($refundId);

            if (!$locked || $locked->status !== RefundRequest::STATUS_PROCESSING) {
                return false;
            }

            $locked->update([
                'status'           => RefundRequest::STATUS_COMPLETED,
                'gateway_status'   => 'completed',
                'gateway_response' => $result['response'],
                'completed_at'     => now(),
            ]);

            $locked->booking?->update(['status' => 'refunded', 'payment_status' => 'refunded']);
            $locked->transaction?->update(['status' => 'refunded']);

            return true;
        });
    }
}
