<?php

namespace TomasManuelTM\ApyPayment\Exceptions;

class PaymentCreationException extends PaymentException
{
    public function __construct(array $context = [], Exception $previous = null)
    {
        parent::__construct(
            "Falha ao criar o pagamento", 
            500, 
            $context, 
            $previous
        );
    }
}