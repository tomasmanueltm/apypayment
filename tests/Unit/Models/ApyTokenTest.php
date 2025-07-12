<?php

namespace TomasManuelTM\ApyPayment\Tests\Unit\Models;

use Carbon\Carbon;
use TomasManuelTM\ApyPayment\Tests\TestCase;
use TomasManuelTM\ApyPayment\Models\ApyToken;

class ApyTokenTest extends TestCase
{
    /** @test */
    public function it_creates_token_record()
    {
        $token = ApyToken::create([
            'token' => 'test-token',
            'client_id' => 'test-client',
            'expires_on' => Carbon::now()->addHour()->timestamp,
            'istoken' => true
        ]);

        $this->assertDatabaseHas('apy_tokens', [
            'id' => $token->id,
            'istoken' => true
        ]);
    }

    /** @test */
    public function it_automatically_serializes_dates()
    {
        $token = ApyToken::create([
            'token' => 'test-token',
            'expires_on' => $expires = Carbon::now()->addHour()->timestamp,
            'istoken' => true
        ]);

        $this->assertEquals($expires, $token->expires_on);
    }

    /** @test */
    public function it_checks_if_token_is_valid()
    {
        $validToken = ApyToken::create([
            'token' => 'valid-token',
            'expires_on' => Carbon::now()->addHour()->timestamp,
            'istoken' => true
        ]);

        $expiredToken = ApyToken::create([
            'token' => 'expired-token',
            'expires_on' => Carbon::now()->subHour()->timestamp,
            'istoken' => true
        ]);

        $this->assertTrue($validToken->isValid());
        $this->assertFalse($expiredToken->isValid());
    }

    /** @test */
    public function it_finds_active_token()
    {
        ApyToken::create([
            'token' => 'active-token',
            'expires_on' => Carbon::now()->addHour()->timestamp,
            'istoken' => true
        ]);

        ApyToken::create([
            'token' => 'inactive-token',
            'expires_on' => Carbon::now()->addHour()->timestamp,
            'istoken' => false
        ]);

        $activeToken = ApyToken::getActiveToken();

        $this->assertEquals('active-token', $activeToken->token);
    }

    /** @test */
    public function it_returns_null_when_no_active_token()
    {
        ApyToken::create([
            'token' => 'inactive-token',
            'expires_on' => Carbon::now()->addHour()->timestamp,
            'istoken' => false
        ]);

        $this->assertNull(ApyToken::getActiveToken());
    }
}