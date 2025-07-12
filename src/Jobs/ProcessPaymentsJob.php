<?php

namespace TomasManuelTM\ApyPayment\Jobs;

use TomasManuelTM\ApyPayment\Services\IPayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPaymentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(IPayService $ipayService)
    {
        $ipayService->processPaymentsAsync();
    }
}