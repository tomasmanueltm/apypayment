<?php

namespace TomasManuelTM\ApyPayment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use TomasManuelTM\ApyPayment\Models\ApyCredential;

class CheckTokenExpirationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle()
    {
        $currentTimestamp = now()->timestamp;
        
        ApyCredential::whereNotNull('expires_on')
            ->where('expires_on', '<', $currentTimestamp)
            ->update(['istoken' => false]);
    }
}