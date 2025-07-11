<?php

namespace TomasManuelTM\ApyPayment\Providers;

use Illuminate\Support\ServiceProvider;
use TomasManuelTM\ApyPayment\Services\ApyService;


class PackageServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('ApyService', function ($app) {
            return new ApyService();
        });
    }

    public function boot()
    {
        // Publicar configurações
        $this->publishes([
            __DIR__.'/../../config/apypayment.php' => config_path('apypayment.php'),
        ], 'config');

        // Publicar migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }
}