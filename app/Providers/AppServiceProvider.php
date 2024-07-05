<?php

namespace App\Providers;

use App\Services\ApiService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Bavix\Wallet\Models\Transaction as BaseTransaction;
use App\Models\Transaction as CustomTransaction;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApiService::class, function ($app) {
            return new ApiService(config('game.api.url'));
        });
        $this->app->bind(BaseTransaction::class, CustomTransaction::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

    }
}
