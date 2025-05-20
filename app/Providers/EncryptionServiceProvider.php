<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EncryptionService;

class EncryptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EncryptionService::class, function ($app) {
            return new EncryptionService();
        });
    }

    public function boot(): void
    {
        //
    }
}
