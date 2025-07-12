<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
   'api_url' => env('APY_API_URL') ? env('APY_API_URL') : 'https://gwy-api-tst.appypay.co.ao/v2.0',
    'auth_url' => env('APY_AUTH_URL') ? env('APY_AUTH_URL')  : 'https://login.microsoftonline.com/appypaydev.onmicrosoft.com/oauth2/token',

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
    'default_currency' => 'AOA',
    'default_payment_method' => 'REF',
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

    // Configurações quando ocorrer pagamentos de uma referência
    'status_update' => [
        // Atualiza o status de pagamento em outras tabelas 
        // automatizando o processo de atualização
        /*[
            'table' => 'orders', // Tabela de pedidos
            'column' => 'payment_status', // Coluna que armazena o status do pagamento
            'payment_key' => 'merchantTransactionId', // Chave para acessar o valor do pagamento
            'expected_value' => null, // qualquer valor
            'new_value' => 'paid' // Novo valor para o status
        ],
        [
            'table' => 'subscriptions', // Tabela de assinaturas
            'column' => 'reference_code', // Coluna que armazena o código de referência
            'payment_key' => 'reference.referenceNumber',  // Chave para acessar o valor de referência
            'expected_value' => 'SUB-', // Exemplo de prefixo
            'new_value' => 'active' // Novo valor para o status
        ]*/
    ],
    
    'storage' => [
        'default_table' => 'apy_payments'
    ],


    'token_check' => [
        'driver' => env('DB_CONNECTION', 'mysql'), // Define o driver padrão
        'batch_size' => 1000, // Para processamento em lotes
    ],
];