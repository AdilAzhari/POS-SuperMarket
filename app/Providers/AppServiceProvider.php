<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ProductService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StockService::class);
        $this->app->singleton(SaleService::class);
        $this->app->singleton(ProductService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
