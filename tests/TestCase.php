<?php

namespace TomasManuelTM\ApyPayment\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TomasManuelTM\ApyPayment\ApyPaymentServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [ApyPaymentServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Configurações de teste
        $app['config']->set('apypayment', [
            'api_url' => 'https://api-test.apypayment.com',
            'auth_url' => 'https://auth-test.apypayment.com',
            // ... outras configurações
        ]);
    }
}