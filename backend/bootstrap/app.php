<?php

declare(strict_types=1);

use App\Exceptions\ApiException;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetLocale;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        // Для всех запросов, а не только для группы api: ответы 404 и 405 появляются до того,
        // как найден маршрут, и тоже должны быть JSON на языке запроса
        $middleware->prepend([ForceJsonResponse::class, SetLocale::class]);

        // За обратным прокси (deploy/): каким прокси верить — config/trustedproxy.php (TRUSTED_PROXIES).
        // Только нужные заголовки: без X-Forwarded-Prefix и заголовков AWS ELB
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);

        // Маршруты модулей получают группу api сами (config/route-attributes.php)
        $middleware->group('api', [SubstituteBindings::class]);
    })
    // Любая ошибка — в общем формате { status, message, data, errors } и на языке запроса.
    // ApiException отвечает сам (ApiException::render), остальное — здесь
    ->withExceptions(static function (Exceptions $exceptions): void {
        $exceptions->dontReport(ApiException::class);

        $exceptions->render(static fn(ValidationException $e): JsonResponse => ApiResponder::error(
            Translator::get('exceptions.validation'),
            $e->status,
            $e->errors(),
        ));

        $exceptions->render(static fn(AuthenticationException $e): JsonResponse => ApiResponder::error(
            Translator::get('exceptions.unauthenticated'),
            401,
        ));

        // 404, 405, 429… Текст исключения не показываем: он английский и для разработчика
        $exceptions->render(static function (HttpExceptionInterface $e): JsonResponse {
            $key = 'exceptions.http.' . $e->getStatusCode();

            return ApiResponder::error(
                Translator::get(Lang::has($key) ? $key : 'exceptions.http.default'),
                $e->getStatusCode(),
                headers: $e->getHeaders(),
            );
        });

        // Непредвиденный сбой. В режиме отладки — стандартный ответ Laravel с трассировкой.
        // HttpResponseException уже несёт готовый ответ: его Laravel отдаст сам
        $exceptions->render(static fn(Throwable $e): ?JsonResponse => $e instanceof HttpResponseException || Config::boolean('app.debug')
            ? null
            : ApiResponder::error(Translator::get('exceptions.server_error'), 500));
    })->create();
