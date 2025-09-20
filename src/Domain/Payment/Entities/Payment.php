<?php

namespace TomasManuelTM\ApyPayment\Domain\Payment\Entities;

use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\Amount;
use TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects\MerchantTransactionId;

class Payment
{
    private MerchantTransactionId $id;
    private Amount $amount;
    private string $description;
    private string $status;
    private ?string $reference;

    public function __construct(
        MerchantTransactionId $id,
        Amount $amount,
        string $description,
        string $status = 'pending',
        ?string $reference = null
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->description = $description;
        $this->status = $status;
        $this->reference = $reference;
    }

    public function getId(): MerchantTransactionId
    {
        return $this->id;
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function capture(): void
    {
        if ($this->status !== 'pending') {
            throw new \DomainException('Payment can only be captured when pending');
        }
        $this->status = 'captured';
    }

    public function complete(): void
    {
        $this->status = 'completed';
    }

    public function fail(): void
    {
        $this->status = 'failed';
    }
}