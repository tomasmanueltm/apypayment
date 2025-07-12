<?php

namespace TomasManuelTM\ApyPayment\Exceptions;

class InvalidRequestException extends PaymentException
{
    public function __construct(array $errors = [], Exception $previous = null)
    {
        parent::__construct(
            "Requisição inválida", 
            400, 
            ['errors' => $errors], 
            $previous
        );
    }
}