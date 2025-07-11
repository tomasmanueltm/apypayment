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
        // Carrega migrations automaticamente
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        
        // Publica migrations para customização (opcional)
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations/vendor/apypayment'),
        ], 'apypayment-migrations');
        
        // Publica seeder
        $this->publishes([
            __DIR__.'/../../database/seeders' => database_path('seeders/vendor/apypayment'),
        ], 'apypayment-seeders');
        
        // Registra comando para rodar a procedure periodicamente
        $this->commands([
            \TomasManuelTM\ApyPayment\Console\Commands\CheckTokenExpiration::class
        ]);
    }
}