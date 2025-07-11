<?php

namespace TomasManuelTM\ApyPayment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTokenExpiration extends Command
{
    protected $signature = 'apy:check-tokens';
    protected $description = 'Verifica e atualiza tokens expirados';

    public function handle()
    {
        DB::unprepared('CALL check_and_update_token_expiration()');
        $this->info('Token expiration check completed.');
    }
}