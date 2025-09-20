<?php

namespace TomasManuelTM\ApyPayment\Domain\Payment\ValueObjects;

class Amount
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        $this->value = $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function equals(Amount $other): bool
    {
        return $this->value === $other->value;
    }
}