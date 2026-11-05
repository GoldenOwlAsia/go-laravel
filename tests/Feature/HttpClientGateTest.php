<?php

namespace Tests\Feature;

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpClientGateTest extends TestCase
{
    public function test_stray_http_requests_are_blocked(): void
    {
        $this->assertTrue(Http::preventingStrayRequests());

        $this->expectException(StrayRequestException::class);

        Http::get('https://example.com/should-not-be-called');
    }
}
