<?php

namespace TomasManuelTM\ApyPayment\Tests\Unit;

use TomasManuelTM\ApyPayment\Services\ApyService;
use TomasManuelTM\ApyPayment\Services\ApyAuth;
use TomasManuelTM\ApyPayment\Tests\TestCase;
use Mockery;

class ApyServiceTest extends TestCase
{
    private $mockAuth;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockAuth = Mockery::mock(ApyAuth::class);
        $this->service = new ApyService($this->mockAuth);
    }

    /** @test */
    public function it_creates_payment_with_valid_data()
    {
        $paymentData = [
            'amount' => 100.00,
            'description' => 'Test payment'
        ];

        $expectedResponse = [
            'success' => true,
            'merchantTransactionId' => 'PT000000001'
        ];

        $this->mockAuth
            ->shouldReceive('create')
            ->once()
            ->with($paymentData)
            ->andReturn($expectedResponse);

        $result = $this->service->createPayment($paymentData);

        $this->assertEquals($expectedResponse, $result);
    }

    /** @test */
    public function it_throws_exception_for_missing_amount()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Campo 'amount' é obrigatório");

        $this->service->createPayment(['description' => 'Test']);
    }

    /** @test */
    public function it_throws_exception_for_invalid_amount()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor deve ser um número positivo');

        $this->service->createPayment([
            'amount' => -100,
            'description' => 'Test'
        ]);
    }

    /** @test */
    public function it_captures_payment()
    {
        $merchantId = 'PT000000001';
        $expectedResponse = ['success' => true, 'status' => 'captured'];

        $this->mockAuth
            ->shouldReceive('capture')
            ->once()
            ->with($merchantId)
            ->andReturn($expectedResponse);

        $result = $this->service->capturePayment($merchantId);

        $this->assertEquals($expectedResponse, $result);
    }

    /** @test */
    public function it_refunds_payment()
    {
        $merchantId = 'PT000000001';
        $amount = 50.00;
        $expectedResponse = ['success' => true, 'refunded' => $amount];

        $this->mockAuth
            ->shouldReceive('refund')
            ->once()
            ->with($merchantId, $amount)
            ->andReturn($expectedResponse);

        $result = $this->service->refundPayment($merchantId, $amount);

        $this->assertEquals($expectedResponse, $result);
    }

    /** @test */
    public function it_gets_payment_status()
    {
        $merchantId = 'PT000000001';
        $expectedResponse = ['success' => true, 'status' => 'completed'];

        $this->mockAuth
            ->shouldReceive('getStatus')
            ->once()
            ->with($merchantId)
            ->andReturn($expectedResponse);

        $result = $this->service->getPaymentStatus($merchantId);

        $this->assertEquals($expectedResponse, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}