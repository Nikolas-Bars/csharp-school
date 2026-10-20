<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\LoginAction;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\SessionResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class LoginController
{
    public function __construct(private LoginAction $login) {}

    #[Post('auth/login', middleware: 'throttle:login')]
    public function __invoke(LoginRequest $request): JsonResponse
    {
        return ApiResponder::ok(
            SessionResource::make($this->login->run($request->toDTO())),
            Translator::get('messages.auth.logged_in'),
        );
    }
}
