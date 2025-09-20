<?php

namespace TomasManuelTM\ApyPayment\Infrastructure\Repositories;

use TomasManuelTM\ApyPayment\Domain\Payment\Entities\Payment;
use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\Amount;
use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\MerchantTransactionId;
use TomasManuelTM\ApyPayment\Domain\Payment\Repositories\PaymentRepositoryInterface;
use TomasManuelTM\ApyPayment\Models\ApyPayment;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function save(Payment $payment): void
    {
        ApyPayment::updateOrCreate(
            ['merchantTransactionId' => $payment->getId()->getValue()],
            [
                'amount' => $payment->getAmount()->getValue(),
                'description' => $payment->getDescription(),
                'status' => $payment->getStatus(),
                'reference' => $payment->getReference(),
            ]
        );
    }

    public function findById(MerchantTransactionId $id): ?Payment
    {
        $model = ApyPayment::where('merchantTransactionId', $id->getValue())->first();
        
        if (!$model) {
            return null;
        }

        return new Payment(
            new MerchantTransactionId($model->merchantTransactionId),
            new Amount($model->amount),
            $model->description,
            $model->status,
            $model->reference
        );
    }

    public function findAll(): array
    {
        return ApyPayment::all()->map(function ($model) {
            return new Payment(
                new MerchantTransactionId($model->merchantTransactionId),
                new Amount($model->amount),
                $model->description,
                $model->status,
                $model->reference
            );
        })->toArray();
    }
}