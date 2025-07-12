<?php

namespace TomasManuelTM\ApyPayment\Console\Commands;

use Illuminate\Console\Command;

class PublishApyPayment extends Command
{
    
    protected $signature = 'apypayment:publish {--force : Overwrite any existing files}';
    
    protected $description = 'Publish all ApyPayment resources (config, migrations, seeders)';


    public function handle()
    {
        $tags = ['apypayment-config', 'apypayment-migrations', 'apypayment-seeders'];
        $force = $this->option('force') ? ['--force' => true] : [];

        foreach ($tags as $tag) {
            $this->call('vendor:publish', array_merge([
                '--provider' => 'TomasManuelTM\ApyPayment\Providers\ApyPaymentServiceProvider',
                '--tag' => $tag,
            ], $force));
        }

        $this->info('All ApyPayment resources published successfully!');
    }
}