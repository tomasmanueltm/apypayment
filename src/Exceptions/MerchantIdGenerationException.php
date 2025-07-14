<?php

namespace TomasManuelTM\ApyPayment\Exceptions;

use Exception;
use RuntimeException;

class MerchantIdGenerationException extends PaymentException 
{
    /**
     * Construtor da exceção de falha na geração do Merchant ID
     * 
     * @param int $attempts Número de tentativas realizadas
     * @param Exception|null $previous Exceção anterior (para encadeamento)
     */
    public function __construct(int $attempts, Exception $previous = null)
    {
        parent::__construct(
            "Falha ao gerar Merchant ID único após {$attempts} tentativas", 
            500, 
            [
                'attempts' => $attempts,
                'error_type' => 'merchant_id_generation_failed'
            ], 
            $previous
        );
    }
}