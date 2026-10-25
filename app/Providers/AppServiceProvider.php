<?php

namespace App\Providers;

use App\Broadcasting\FailSoftReverbBroadcaster;
use App\Services\MailService;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MailService::class, function ($app) {
            return new MailService();
        });

        // The money screens and the payout service must all look at the SAME "which bookings disagree with their money" answer,
        // so that forgetting it after a change reaches every one of them
        $this->app->singleton(\App\Services\Money\MoneyChecks::class);
        $this->app->singleton(\App\Services\Money\MoneyReport::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // the owner hears when a refund on one of their bookings is approved, declined or paid
        \App\Models\RefundRequest::updated(function (\App\Models\RefundRequest $refund) {
            if ($refund->wasChanged('status')) {
                app(\App\Services\Notify\VendorAlerts::class)->refundChanged($refund);
                // ...and so does the guest who asked for it (a bell only: it never holds a refund up)
                app(\App\Services\Notify\GuestAlerts::class)->refundChanged($refund);
            }
        });

        // Live pushes must never fail the request that triggered them
        $this->app->make(BroadcastManager::class)->extend('reverb', function ($app, array $config) {
            return new FailSoftReverbBroadcaster($app->make(BroadcastManager::class)->pusher($config));
        });

        Blade::directive('hasPermission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission({$permission})): ?>";
        });

        Blade::directive('endHasPermission', function () {
            return "<?php endif; ?>";
        });
    }
}
