<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ExtractService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el ExtractService como singleton
        $this->app->singleton(ExtractService::class, function ($app) {
            return new ExtractService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}