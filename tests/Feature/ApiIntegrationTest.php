<?php

namespace TomasManuelTM\ApyPayment\Tests\Feature;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use TomasManuelTM\ApyPayment\Services\ApyService;
use TomasManuelTM\ApyPayment\Tests\TestCase;


class ApiIntegrationTest extends TestCase
{
    private function createMockService(array $responses)
    {
        $mock = new MockHandler($responses);
        $handler = HandlerStack::create($mock);
        
        return new ApyService($handler);
    }

    /** @test */
    public function it_gets_access_token_successfully()
    {
        $mockResponses = [
            new Response(200, [], json_encode([
                'access_token' => 'test-token',
                'expires_in' => 3600
            ]))
        ];

        $service = $this->createMockService($mockResponses);
        $token = $service->getAccessToken();

        $this->assertEquals('test-token', $token);
    }
}