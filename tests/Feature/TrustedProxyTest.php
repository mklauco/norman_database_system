<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/client-ip', fn (Request $request): string => (string) $request->ip());
    }

    public function test_forwarded_ip_from_a_public_address_is_ignored(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
            ->withHeader('X-Forwarded-For', '198.51.100.7')
            ->get('/_test/client-ip')
            ->assertSeeText('203.0.113.5');
    }

    public function test_forwarded_ip_from_the_docker_proxy_is_used(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
            ->withHeader('X-Forwarded-For', '198.51.100.7')
            ->get('/_test/client-ip')
            ->assertSeeText('198.51.100.7');
    }
}
