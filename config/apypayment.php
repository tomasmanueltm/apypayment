<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api_url' => env('APY_API_URL', 'https://gwy-api-tst.appypay.co.ao/v2.0'),
    'auth_url' => env('APY_AUTH_URL', 'https://login.microsoftonline.com/appypaydev.onmicrosoft.com/oauth2/token'),

    /*
    |--------------------------------------------------------------------------
    | Credenciais Cliente
    |--------------------------------------------------------------------------
    */
    'client_id' => env('APY_CLIENT_ID'),
    'client_secret' => env('APY_CLIENT_SECRET'),


    /*
    |--------------------------------------------------------------------------
    | Configurações de Parametros para Token
    |--------------------------------------------------------------------------
    */
    'grant_type' => env('APY_GRANT_TYPE', 'client_credentials'),
    'resource' => env('APY_RESOURCE', '2aed7612-de64-46b5-9e59-1f48f8902d14'),
    
    /*
    |--------------------------------------------------------------------------
    | Configurações de Parametros Header
    |--------------------------------------------------------------------------
    */
    'assertion' => '',
    'accept_language' => 'pt-BR',
    'accept' => 'application/json',
    'content_type' => 'application/json',


    /*
    |--------------------------------------------------------------------------
    | Configurações de Pagamento
    |--------------------------------------------------------------------------
    */
    'default_currency' => 'BRL',
    'default_payment_method' => 'REF_',
    'timeout' => 30,
    'prefixes' => [
        'default' => 'PS',
        'renewal' => 'PC',
    ],
    
    'tables' => [
        'apy_payments' => [
            'columns' => [
                'reference' => 'reference->referenceNumber',
                'merchant_transaction_id' => 'merchantTransactionId'
            ],
            'model' => \TomasManuelTM\ApyPayment\Models\ApyPayment::class
        ],
        // Outras tabelas podem ser adicionadas aqui
    ],
    
    'storage' => [
        'default_table' => 'apy_payments'
    ],


    'token_check' => [
        'driver' => env('DB_CONNECTION', 'mysql'), // Define o driver padrão
        'batch_size' => 1000, // Para processamento em lotes
    ],
];