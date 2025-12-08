<?php

namespace TomasManuelTM\ApyPayment\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use TomasManuelTM\ApyPayment\Events\PaymentProcessEvent;
use TomasManuelTM\ApyPayment\Listeners\ProcessPaymentListener;

class ApyPaymentEventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentProcessEvent::class => [
            ProcessPaymentListener::class,
        ],
    ];
}