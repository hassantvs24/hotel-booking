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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

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
