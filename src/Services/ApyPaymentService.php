<?php

namespace TomasManuelTM\ApyPayment\Services;

use TomasManuelTM\ApyPayment\Models\ApyCredential;
use TomasManuelTM\ApyPayment\Models\ApyPayment;

class ApyPaymentService
{
    protected $credentials;
    
    public function __construct()
    {
        $this->credentials = ApyCredential::firstOrFail();
    }
    
    public function createPayment(array $data): ApyPayment
    {
        return ApyPayment::create(array_merge($data, [
            'createdDate' => now(),
            'updatedDate' => now()
        ]));
    }
    
    public function getCredentials(): ApyCredential
    {
        return $this->credentials;
    }
    
    // Outros métodos do serviço...
}