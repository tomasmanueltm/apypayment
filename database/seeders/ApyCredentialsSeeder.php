<?php

namespace TomasManuelTM\ApyPayment\Database\Seeders;

use Illuminate\Database\Seeder;
use TomasManuelTM\ApyPayment\Models\ApyCredential;

class ApyCredentialsSeeder extends Seeder
{
    public function run()
    {
        ApyCredential::create([
            'client_id' => env('APY_CLIENT_ID'),
            'client_secret' => env('APY_CLIENT_SECRET'),
            'resource' => (env('APY_RESOURCE') ?? '2aed7612-de64-46b5-9e59-1f48f8902d14'),
            'grant_type' => (env('APY_GRANT_TYPE') ?? 'client_credentials'),
            'auth_url' => (env('APY_AUTH_URL') ?? 'https://login.microsoftonline.com/appypaydev.onmicrosoft.com/oauth2/token'),
            'api_url' => (env('APY_API_URL') ?? 'https://gwy-api-tst.appypay.co.ao/v2.0'),
            'istoken' => false
        ]);
    }
}