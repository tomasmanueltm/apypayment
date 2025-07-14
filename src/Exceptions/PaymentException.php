<?php

namespace TomasManuelTM\ApyPayment\Exceptions;

use Exception as BaseException;

class PaymentException extends BaseException
{
    protected $context;

    public function __construct(
        string $message = "", 
        int $code = 0, 
        array $context = [], 
        BaseException $previous = null // Alterado para a classe base Exception do PHP
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}