<?php

namespace TomasManuelTM\ApyPayment\Providers;

use Illuminate\Support\ServiceProvider;
use TomasManuelTM\ApyPayment\Logs\ApyLogger;
use TomasManuelTM\ApyPayment\Services\ApyAuth;
use TomasManuelTM\ApyPayment\Services\ApyService;
use TomasManuelTM\ApyPayment\Services\ApyPaymentService;
use TomasManuelTM\ApyPayment\Console\Commands\CheckTokenExpiration;
use TomasManuelTM\ApyPayment\Console\Commands\PublishApyPayment;
use TomasManuelTM\ApyPayment\Facades\ApyPaymentFacade;
use GuzzleHttp\Client;

class ApyPaymentServiceProvider extends ServiceProvider
{
    /**
     * Registra os serviços do pacote.
     */
    public function register()
    {
        $this->registerConfiguration();
        $this->registerMainService();
        $this->registerSharedServices();
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
        // Registro do ApyService com alias para facade
        $this->app->singleton('ApyService', function ($app) {
            $httpClient = new Client([
                'timeout' => config('apypayment.http_timeout', 30),
                'verify' => config('apypayment.http_verify_ssl', false),
            ]);
            
            $authService = new ApyAuth($httpClient);
            return new ApyService($authService);
        });

        // Registro adicional para injeção de dependência por classe
        $this->app->singleton(ApyService::class, function ($app) {
            return $app->make('ApyService');
        });
            

        // Registro do facade
        $this->app->alias('ApyService', ApyPaymentFacade::class);
    }


    protected function registerSharedServices()
    {
        // Registro do Logger
        $this->app->singleton(ApyLogger::class, function ($app) {
            return new ApyLogger();
        });
        
        // Registro do Logger com alias para facilitar acesso
        $this->app->alias(ApyLogger::class, 'apylogger');
        
        // Registro do BaseService 
         $this->app->singleton(ApyBase::class, function ($app) {
             return new ApyBase();
         });
        
        // Registro do Base com alias para facilitar acesso
        $this->app->alias(ApyBase::class, 'apybase');
    }



    /**
     * Publica o arquivo de configuração.
     */
    protected function publishConfiguration()
    {
        $this->publishes([
            __DIR__.'/../../config/apypayment.php' => config_path('apypayment.php'),
        ], ['apypayment-config', 'apypayment']);
    }

    /**
     * Publica as migrações do pacote.
     */
    protected function publishMigrations()
    {
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], ['apypayment-migrations', 'apypayment']);
    }

    /**
     * Publica os seeders do pacote (se existirem).
     */
    protected function publishSeeders()
    {
        if (is_dir($seedersPath = __DIR__.'/../../database/seeders')) {
            $this->publishes([
                $seedersPath => database_path('seeders'),
            ], ['apypayment-seeders', 'apypayment']);
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