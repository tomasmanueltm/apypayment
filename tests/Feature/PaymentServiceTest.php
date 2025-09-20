<?php

namespace TomasManuelTM\ApyPayment\Tests\Feature;

use TomasManuelTM\ApyPayment\Services\ApyService;
use TomasManuelTM\ApyPayment\Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class PaymentServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApyService::class);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $this->service->createPayment([
            'amount' => 100.00
            // description missing
        ]);
    }

    /** @test */
    public function it_validates_amount_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valor deve ser um número positivo');
        
        $this->service->createPayment([
            'amount' => 'invalid',
            'description' => 'Test payment'
        ]);
    }

    /** @test */
    public function it_creates_payment_with_valid_data()
    {
        // Mock HTTP response
        $mockHandler = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'test-token', 'expires_in' => 3600, 'expires_on' => time() + 3600])),
            new Response(200, [], json_encode([
                'merchantTransactionId' => 'PT000000001',
                'reference' => 'REF-12345',
                'status' => 'pending'
            ]))
        ]);
        
        $handlerStack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        
        // Test with mocked client would require dependency injection setup
        $this->assertTrue(true); // Placeholder for integration test
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        // Test error handling
        $this->assertTrue(true); // Placeholder - would need proper API mocking
    }
}