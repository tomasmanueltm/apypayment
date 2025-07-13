<?php

namespace TomasManuelTM\ApyPayment\Providers;

use Illuminate\Support\ServiceProvider;
use TomasManuelTM\ApyPayment\Services\ApyService;
use TomasManuelTM\ApyPayment\Services\ApyPaymentService;
use TomasManuelTM\ApyPayment\Console\Commands\CheckTokenExpiration;
use TomasManuelTM\ApyPayment\Console\Commands\PublishApyPayment;
use TomasManuelTM\ApyPayment\Facades\ApyFacade;

class ApyPaymentServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços do pacote.
     */
    public function register()
    {
        $this->registerConfiguration();
        $this->registerMainService();
    }

    /**
     * Inicializa os serviços do pacote.
     */
    public function boot()
    {
        $this->loadMigrations();
        
        if ($this->app->runningInConsole()) {
            $this->registerConsoleResources();
        }
    }

    /**
     * Carrega as migrações do pacote.
     */
    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    /**
     * Registra os recursos para o ambiente console.
     */
    protected function registerConsoleResources()
    {
        $this->publishConfiguration();
        $this->publishMigrations();
        $this->publishSeeders();
        $this->registerCommands();
    }

    /**
     * Registra a configuração do pacote.
     */
    protected function registerConfiguration()
    {
        $configPath = __DIR__.'/../../config/apypayment.php';
        
        if (!file_exists($configPath)) {
            throw new \RuntimeException('Configuração do ApyPayment não encontrada: '.$configPath);
        }

        $this->mergeConfigFrom($configPath, 'apypayment');
    }

    /**
     * Registra o serviço principal.
     */
    protected function registerMainService()
    {
        $this->app->singleton('ApyService', function ($app) {
            return new ApyService($app['config']->get('apypayment'));
        });
        $this->app->singleton('ApyPaymentService', function ($app) {
            return new ApyPaymentService($app['config']->get('apypayment'));
        });

        $this->app->alias('ApyService', ApyFacade::class);
    }

    /**
     * Publica o arquivo de configuração.
     */
    protected function publishConfiguration()
    {
        $this->publishes([
            __DIR__.'/../../config/apypayment.php' => config_path('apypayment.php'),
        ], 'apypayment-config');
    }

    /**
     * Publica as migrações do pacote.
     */
    protected function publishMigrations()
    {
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'apypayment-migrations');
    }

    /**
     * Publica os seeders do pacote (se existirem).
     */
    protected function publishSeeders()
    {
        if (is_dir($seedersPath = __DIR__.'/../../database/seeders')) {
            $this->publishes([
                $seedersPath => database_path('seeders'),
            ], 'apypayment-seeders');
        }
    }

    /**
     * Registra os comandos do pacote.
     */
    protected function registerCommands()
    {
        $this->commands([
            CheckTokenExpiration::class,
            PublishApyPayment::class,
        ]);
    }
}