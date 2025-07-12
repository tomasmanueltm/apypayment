<?php

namespace TomasManuelTM\ApyPayment\Exceptions;

class MerchantIdGenerationException extends PaymentException
{
    public function __construct(int $attempts, Exception $previous = null)
    {
        parent::__construct(
            "Falha ao gerar Merchant ID único", 
            500, 
            ['attempts' => $attempts], 
            $previous
        );
    }
}