<?php

namespace TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects;

class MerchantTransactionId
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('MerchantTransactionId cannot be empty');
        }
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(MerchantTransactionId $other): bool
    {
        return $this->value === $other->value;
    }
}