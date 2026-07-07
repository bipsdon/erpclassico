<?php

namespace App\Providers;

use App\Services\Scheduling\SchedulingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind as singleton — schedule data is rebuilt fresh per request,
        // so sharing one instance per request cycle is safe and avoids
        // redundant DB queries within the same request.
        $this->app->singleton(SchedulingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
