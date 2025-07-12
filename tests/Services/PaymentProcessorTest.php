<?php

namespace TomasManuelTM\ApyPayment\Tests\Unit\Services;

use Mockery;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use TomasManuelTM\ApyPayment\Tests\TestCase;
use TomasManuelTM\ApyPayment\Services\PaymentProcessor;
use TomasManuelTM\ApyPayment\Models\ApyToken;

class PaymentProcessorTest extends TestCase
{
    private PaymentProcessor $processor;
    private $httpMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Configuração inicial para todos os testes
        config([
            'apypayment.api_url' => 'https://api.payment.com',
            'apypayment.auth_url' => 'https://auth.payment.com',
            'apypayment.client_id' => 'test-client',
            'apypayment.client_secret' => 'test-secret'
        ]);

        // Mock do cliente HTTP
        $this->httpMock = Mockery::mock(Client::class);
        $this->processor = new PaymentProcessor($this->httpMock);
    }

    /** @test */
    public function it_gets_access_token_successfully()
    {
        // Mock da resposta da API
        $this->httpMock->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'test-token',
                'expires_in' => 3600
            ])));

        $token = $this->processor->getAccessToken();

        $this->assertEquals('test-token', $token);
        $this->assertDatabaseHas('apy_tokens', [
            'istoken' => true
        ]);
    }

    /** @test */
    public function it_handles_failed_token_authentication()
    {
        $this->httpMock->shouldReceive('post')
            ->once()
            ->andThrow(new \GuzzleHttp\Exception\RequestException(
                'Error',
                Mockery::mock(\GuzzleHttp\Psr7\Request::class)
            ));

        $token = $this->processor->getAccessToken();

        $this->assertNull($token);
    }

    /** @test */
    public function it_processes_payment_successfully()
    {
        // Primeiro mock para obter token
        $this->httpMock->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'test-token',
                'expires_in' => 3600
            ])));

        // Mock para a chamada de pagamento
        $this->httpMock->shouldReceive('post')
            ->once()
            ->withArgs(function ($url, $options) {
                return $url === 'https://api.payment.com/charges' &&
                       $options['headers']['Authorization'] === 'Bearer test-token';
            })
            ->andReturn(new Response(200, [], json_encode([
                'id' => 'pay_123',
                'status' => 'success'
            ])));

        $result = $this->processor->createPayment([
            'amount' => 100.00,
            'currency' => 'USD'
        ]);

        $this->assertEquals('success', $result['status']);
    }

    /** @test */
    public function it_updates_expired_tokens()
    {
        // Cria um token expirado
        ApyToken::create([
            'token' => 'old-token',
            'expires_on' => now()->subDay()->timestamp,
            'istoken' => true
        ]);

        // Mock da nova resposta de token
        $this->httpMock->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'new-token',
                'expires_in' => 3600
            ])));

        $token = $this->processor->getAccessToken();

        $this->assertEquals('new-token', $token);
        $this->assertDatabaseMissing('apy_tokens', [
            'token' => 'old-token',
            'istoken' => true
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}