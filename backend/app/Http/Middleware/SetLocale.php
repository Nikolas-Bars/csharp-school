<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Язык ответа — из заголовка Accept-Language (его шлёт фронт). Языка нет среди
 * app.supported_locales или заголовка нет — первый язык списка.
 */
final class SetLocale
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $supported */
        $supported = Config::array('app.supported_locales');

        App::setLocale($request->getPreferredLanguage($supported) ?? $supported[0]);

        return $next($request);
    }
}
