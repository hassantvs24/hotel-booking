<?php

namespace App\Console\Commands;

use App\Jobs\CheckRefundStatuses;
use App\Models\RefundRequest;
use App\Services\Refund\SslCommerzRefundService;
use Illuminate\Console\Command;

/**
 * Ask SSLCommerz right now how the refunds still "processing" are doing.
 * The scheduler does the same every 5 minutes; this is for when you don't want to wait.
 */
class CheckRefundStatusesCommand extends Command
{
    protected $signature = 'refunds:check-status';

    protected $description = 'Ask SSLCommerz how the refunds still processing are doing, and finish the ones that are done';

    public function handle(SslCommerzRefundService $gateway): int
    {
        $waiting = RefundRequest::where('status', RefundRequest::STATUS_PROCESSING)
            ->whereNotNull('gateway_refund_id')
            ->where('gateway_refund_id', 'not like', 'LOCAL-REFUND-%')
            ->get(['id', 'refund_number', 'gateway_refund_id']);

        if ($waiting->isEmpty()) {
            $this->info('No refund is waiting on SSLCommerz.');

            return self::SUCCESS;
        }

        (new CheckRefundStatuses())->handle($gateway);

        RefundRequest::whereIn('id', $waiting->pluck('id'))->get()->each(function (RefundRequest $refund): void {
            $this->line(sprintf(
                '%s  [%s]  SSLCommerz ref %s  -> %s / gateway says "%s"',
                $refund->refund_number,
                $refund->id,
                $refund->gateway_refund_id,
                strtoupper($refund->status),
                $refund->gateway_status
            ));
        });

        return self::SUCCESS;
    }
}
