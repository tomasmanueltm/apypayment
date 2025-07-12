<?php

namespace TomasManuelTM\ApyPayment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTokenExpiration extends Command
{
    protected $signature = 'apypayment:token';
    protected $description = 'Verifica e atualiza token expirado';

    public function handle()
    {
        if (config('database.default') === 'mysql') {
            DB::unprepared('CALL check_and_update_token_expiration()');
        } else {
            CheckTokenExpirationJob::dispatch();
        }
        $this->info('Token expiration check completed.');
    }
}