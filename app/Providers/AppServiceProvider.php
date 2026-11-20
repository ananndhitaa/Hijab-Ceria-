<?php

namespace App\Providers;

use App\Services\Platform\PlatformNotificationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Globally share synchronized actionable tasks and notification counters to all Super Admin platform views
        View::composer([
            'platform.layouts.app',
            'platform.dashboard',
            'platform.partials.topbar-notifications',
        ], function ($view) {
            $view->with('platformActionableTasks', PlatformNotificationService::getActionableTasks());
        });
    }
}
