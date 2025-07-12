<?php

namespace TomasManuelTM\ApyPayment\Tests\Feature;

use TomasManuelTM\ApyPayment\Facades\ApyFacade;
use TomasManuelTM\ApyPayment\Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    /** @test */
    public function it_processes_payments_correctly()
    {
        $response = ApyFacade::createPayment([
            'amount' => 100.00,
            'currency' => 'AOA'
        ]);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('AOA', $response['currency']);
    }

    /** @test */
    public function it_handles_failed_payments()
    {
        $this->mockHttpErrorResponse();
        
        $response = ApyFacade::createPayment([
            'amount' => -100.00, // Valor inválido
            'currency' => 'AOA'
        ]);

        $this->assertNull($response);
    }
}