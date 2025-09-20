<?php

namespace TomasManuelTM\ApyPayment\Domain\Payment\Repositories;

use TomasManuelTM\ApyPayment\Domain\Payment\Entities\Payment;
use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\MerchantTransactionId;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
    
    public function findById(MerchantTransactionId $id): ?Payment;
    
    public function findAll(): array;
}