<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\CurrentUserAction;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\RouteAttributes\Attributes\Get;

final readonly class MeController
{
    public function __construct(private CurrentUserAction $currentUser) {}

    #[Get('auth/me', middleware: 'auth:sanctum')]
    public function __invoke(): JsonResponse
    {
        return ApiResponder::ok(
            UserResource::make($this->currentUser->run((int) Auth::id())),
            Translator::get('messages.auth.me'),
        );
    }
}
