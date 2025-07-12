<?php

namespace TomasManuelTM\ApyPayment\Exceptions;

class PaymentNotFoundException extends PaymentException
{
    public function __construct($paymentId, Exception $previous = null)
    {
        parent::__construct(
            "Pagamento não encontrado", 
            404, 
            ['payment_id' => $paymentId], 
            $previous
        );
    }
}