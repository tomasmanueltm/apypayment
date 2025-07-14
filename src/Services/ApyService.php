<?php

namespace TomasManuelTM\ApyPayment\Services;

use ApyLogger;
use Carbon\Carbon;
use TomasManuelTM\ApyPayment\Services\ApyAuth;
use ApyBaseService;
use RuntimeException;
use Illuminate\Support\Collection;
use TomasManuelTM\ApyPayment\Models\ApySys;
use TomasManuelTM\ApyPayment\Models\ApyMethod;
use TomasManuelTM\ApyPayment\Models\ApyPayment;


class ApyService
{
    private ApyAuth $client;

    public function __construct(ApyAuth $auth) {
        $this->client = $auth;
    }

    public function getToken(){
        return $this->client->getAccessToken();
    }
}