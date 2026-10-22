<?php

namespace App\Services\Refund;

use App\Jobs\CheckRefundStatuses;
use App\Models\RefundRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * SSLCommerz tells nobody when it has finished a refund — someone has to ask. The scheduler asks every minute,
 * but only while `php artisan schedule:work` is running. This is the safety net that does not depend on it:
 * whenever the refund list is opened (admin panel, or the guest's My Bookings) any refund still waiting on
 * SSLCommerz is checked right after the page has been sent, so opening the page is enough to finish it and
 * send the guest the "refund complete" email.
 */
class RefundStatusRefresher
{
    /** Never ask more often than this, however many people open the page */
    private const PAUSE_SECONDS = 20;

    /** Call from a controller; the real work happens after the response has gone out */
    public static function afterResponse(): void
    {
        app()->terminating(function (): void {
            try {
                self::run();
            } catch (\Throwable $e) {
                Log::warning('Refund status refresh failed: ' . $e->getMessage());
            }
        });
    }

    public static function run(): void
    {
        $waiting = CheckRefundStatuses::needsAttention()->exists();

        // Nothing waiting → do not even touch the cache; and one asker at a time
        if (!$waiting || !Cache::add('refund-status-refresh', 1, self::PAUSE_SECONDS)) {
            return;
        }

        (new CheckRefundStatuses())->handle(app(SslCommerzRefundService::class));
    }
}
