<?php

namespace TomasManuelTM\ApyPayment\Services;

use Carbon\Carbon;
use RuntimeException;
use Illuminate\Support\Collection;
use TomasManuelTM\ApyPayment\Models\ApySys;
use TomasManuelTM\ApyPayment\Services\ApyBase;
use TomasManuelTM\ApyPayment\Services\ApyAuth;
use TomasManuelTM\ApyPayment\Models\ApyMethod;
use TomasManuelTM\ApyPayment\Models\ApyPayment;


class ApyService extends ApyBase
{
    private ApyAuth $auth;

    public function __construct(ApyAuth $auth) {
        $this->auth = $auth;

    }
    
    /**
     * Obter todos metodos de pagamento disponíveis
     */
    public function getApplications() : Array
    {
        $response = $this->auth->applications(); 
        $this->setMethods($response);
        return ($response)  ? json_decode($response->getBody(), true) : [];
    }

    /**
     * Obter todos os pagamentos 
     */
    public function getPayments(): Array
    {
        $response = $this->auth->payments();
        $this->setPayments($response);
        return ($response) ? json_decode($response->getBody(), true) :  [];
    }


    public function createPayment(array $json) {
      return  $this->auth->create($json);
    }


}