<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\LogoutAction;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class LogoutController
{
    public function __construct(private LogoutAction $logout) {}

    #[Post('auth/logout', middleware: 'auth:sanctum')]
    public function __invoke(Request $request): JsonResponse
    {
        // Токен «{id}|{секрет}» уже проверен auth:sanctum; удаляется он по id и только у своего владельца
        $tokenId = (int) Str::before((string) $request->bearerToken(), '|');

        $this->logout->run($tokenId, (int) Auth::id());

        return ApiResponder::ok(null, Translator::get('messages.auth.logged_out'));
    }
}
