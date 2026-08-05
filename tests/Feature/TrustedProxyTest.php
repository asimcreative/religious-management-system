<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_the_configured_proxy_forwards_the_https_scheme(): void
    {
        Route::get('/__test/trusted-proxy-scheme', fn (Request $request) => response()->json([
            'scheme' => $request->getScheme(),
            'secure' => $request->secure(),
        ]));

        $this->withServerVariables([
            'REMOTE_ADDR' => '172.20.0.10',
        ])->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('/__test/trusted-proxy-scheme')
            ->assertOk()
            ->assertExactJson([
                'scheme' => 'https',
                'secure' => true,
            ]);
    }
}
