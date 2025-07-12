<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TomasManuelTM\ApyPayment\Models\ApyToken;

class ApyTokenSeeder extends Seeder
{
    public function run()
    {
        ApyToken::create([
            'token' => 'your_token_here',
            'istoken' => false
        ]);
    }
}