<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * За обратным прокси (deploy/) API видит настоящий IP ученика и https — но только если запрос
 * пришёл от доверенного прокси (TRUSTED_PROXIES); подделанные заголовки от остальных не действуют.
 */
final class TrustProxiesTest extends TestCase
{
    private const array FORWARDED = [
        'X-Forwarded-For'   => '203.0.113.7',
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host'  => 'csharp.example.org',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('api/test/client', static fn(Request $request): JsonResponse => new JsonResponse([
            'ip'     => $request->ip(),
            'secure' => $request->isSecure(),
            'url'    => $request->url(),
        ]));
    }

    public function testTrustedProxyForwardsClientIpAndHttps(): void
    {
        Config::set('trustedproxy.proxies', '10.0.0.0/8, 172.16.0.0/12');

        $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
            ->getJson('/api/test/client', self::FORWARDED)
            ->assertExactJson(['ip' => '203.0.113.7', 'secure' => true, 'url' => 'https://csharp.example.org/api/test/client']);
    }

    public function testForwardedHeadersFromUntrustedAddressAreIgnored(): void
    {
        Config::set('trustedproxy.proxies', '10.0.0.0/8');

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->getJson('/api/test/client', self::FORWARDED)
            ->assertJson(['ip' => '198.51.100.9', 'secure' => false]);
    }

    public function testNoProxyIsTrustedByDefault(): void
    {
        Config::set('trustedproxy.proxies', null);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
            ->getJson('/api/test/client', self::FORWARDED)
            ->assertJson(['ip' => '10.0.0.2', 'secure' => false]);
    }
}
