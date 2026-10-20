<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API отвечает JSON, даже если клиент не прислал Accept: application/json. Без этого Laravel
 * на ошибке входа пытается перенаправить на страницу логина, которой у API нет.
 */
final class ForceJsonResponse
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
