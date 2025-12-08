<?php

namespace TomasManuelTM\ApyPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentProcessEvent
{
    use Dispatchable, SerializesModels;

    public array $paymentData;

    public function __construct(array $paymentData)
    {
        $this->paymentData = $paymentData;
    }
}