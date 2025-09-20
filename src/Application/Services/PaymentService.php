<?php

namespace TomasManuelTM\ApyPayment\Application\Services;

use TomasManuelTM\ApyPayment\Domain\Payment\Entities\Payment;
use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\Amount;
use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\MerchantTransactionId;
use TomasManuelTM\ApyPayment\Domain\Payment\Repositories\PaymentRepositoryInterface;

class PaymentService
{
    private PaymentRepositoryInterface $repository;

    public function __construct(PaymentRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function createPayment(array $data): Payment
    {
        $id = new MerchantTransactionId($this->generateId());
        $amount = new Amount($data['amount']);
        
        $payment = new Payment(
            $id,
            $amount,
            $data['description'],
            'pending',
            $data['reference'] ?? null
        );

        $this->repository->save($payment);
        
        return $payment;
    }

    public function capturePayment(string $merchantId): Payment
    {
        $id = new MerchantTransactionId($merchantId);
        $payment = $this->repository->findById($id);
        
        if (!$payment) {
            throw new \DomainException('Payment not found');
        }

        $payment->capture();
        $this->repository->save($payment);
        
        return $payment;
    }

    private function generateId(): string
    {
        return 'PT' . str_pad(random_int(1, 999999999), 9, '0', STR_PAD_LEFT);
    }
}