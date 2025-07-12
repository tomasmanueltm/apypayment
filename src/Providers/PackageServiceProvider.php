<?php

namespace TomasManuelTM\ApyPayment\Providers;

use Illuminate\Support\ServiceProvider;
use TomasManuelTM\ApyPayment\Services\ApyService;
use TomasManuelTM\ApyPayment\Console\Commands\CheckTokenExpiration;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $configPath = __DIR__.'/../../config/apypayment.php';
        
        if (file_exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'apypayment');
        }

        $this->app->singleton('ApyService', function ($app) {
            return new ApyService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Só carrega recursos no console
        if ($this->app->runningInConsole()) {
            $this->publishResources();
            $this->registerCommands();
        }

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    /**
     * Publica recursos do package
     */
    protected function publishResources()
    {
        // Configurações
        $this->publishes([
            __DIR__.'/../../config/apypayment.php' => config_path('apypayment.php'),
        ], 'apypayment-config');

        // Migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations/vendor/apypayment'),
        ], 'apypayment-migrations');

        // Seeders
        if (is_dir(__DIR__.'/../../database/seeders')) {
            $this->publishes([
                __DIR__.'/../../database/seeders' => database_path('seeders/vendor/apypayment'),
            ], 'apypayment-seeders');
        }
    }

    /**
     * Registra comandos do package
     */
    protected function registerCommands()
    {
        $this->commands([
            CheckTokenExpiration::class,
        ]);
    }
}