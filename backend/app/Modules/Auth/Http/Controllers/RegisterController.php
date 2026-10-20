<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\RegisterAction;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\SessionResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class RegisterController
{
    public function __construct(private RegisterAction $register) {}

    #[Post('auth/register', middleware: 'throttle:auth')]
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        return ApiResponder::created(
            SessionResource::make($this->register->run($request->toDTO())),
            Translator::get('messages.auth.registered'),
        );
    }
}
