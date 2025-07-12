<?php

namespace TomasManuelTM\ApyPayment\Tests\Unit\Models;

use TomasManuelTM\ApyPayment\Models\ApyPayment;
use TomasManuelTM\ApyPayment\Tests\TestCase;

class ApyPaymentTest extends TestCase
{
    /** @test */
    public function it_can_create_payment_record()
    {
        $payment = ApyPayment::create([
            'paymentMethod' => 'TEST123',
            'merchantTransactionId' => 'TEST123',
            'status' => 'Pending',
            'amount' => 100.00,
            'currency' => 'AOA',
        ]);

        $this->assertDatabaseHas('apy_payments', [
            'merchantTransactionId' => 'TEST123',
            'status' => 'Pending'
        ]);
    }
}