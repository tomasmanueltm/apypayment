<?php

namespace TomasManuelTM\ApyPayment\Exceptions;

use Exception;

class PaymentException extends Exception
{
    /**
     * Dados adicionais sobre o erro
     * @var array
     */
    protected $context;

    /**
     * Construtor da exceção
     * 
     * @param string $message Mensagem de erro
     * @param int $code Código de erro
     * @param array $context Contexto adicional
     * @param Exception|null $previous Exceção anterior
     */
    public function __construct(
        string $message = "", 
        int $code = 0, 
        array $context = [], 
        Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Obtém o contexto do erro
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}