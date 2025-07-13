<?php

namespace TomasManuelTM\ApyPayment\Facades;

use Illuminate\Support\Facades\Facade;

class ApyPaymentFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'ApyService';
    }
}