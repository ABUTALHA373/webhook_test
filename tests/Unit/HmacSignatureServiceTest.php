<?php

namespace Tests\Unit;

use App\Services\HmacSignatureService;
use PHPUnit\Framework\TestCase;

class HmacSignatureServiceTest extends TestCase
{
    protected HmacSignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HmacSignatureService;
    }

    public function test_generates_hex_hmac(): void
    {
        $payload = '{"event":"test"}';
        $secret = 'secret_123';
        $expected = hash_hmac('sha256', $payload, $secret);

        $sig = $this->service->generate($payload, $secret, 'sha256', 'hex');
        $this->assertEquals($expected, $sig);
    }

    public function test_generates_base64_hmac(): void
    {
        $payload = '{"event":"test"}';
        $secret = 'secret_123';
        $expected = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        $sig = $this->service->generate($payload, $secret, 'sha256', 'base64');
        $this->assertEquals($expected, $sig);
    }

    public function test_generates_stripe_format(): void
    {
        $payload = '{"event":"test"}';
        $secret = 'whsec_test';

        $sig = $this->service->generate($payload, $secret, 'sha256', 'stripe');
        $this->assertStringStartsWith('t=', $sig);
        $this->assertStringContainsString(',v1=', $sig);
    }

    public function test_build_header_returns_correct_array(): void
    {
        $header = $this->service->buildHeader('{"a":1}', 'my_secret', 'X-Hub-Signature-256', 'sha256', 'prefix_hex');
        $this->assertArrayHasKey('X-Hub-Signature-256', $header);
        $this->assertStringStartsWith('sha256=', $header['X-Hub-Signature-256']);
    }
}
