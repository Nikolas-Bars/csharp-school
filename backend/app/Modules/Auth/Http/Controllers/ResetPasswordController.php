<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\ResetPasswordAction;
use App\Modules\Auth\Http\Requests\ResetPasswordRequest;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class ResetPasswordController
{
    public function __construct(private ResetPasswordAction $resetPassword) {}

    #[Post('auth/reset-password', middleware: 'throttle:password-reset')]
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->resetPassword->run($request->toDTO());

        return ApiResponder::ok(null, Translator::get('messages.auth.password_reset'));
    }
}
