<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Actions\SendPasswordResetLinkAction;
use App\Modules\Auth\Http\Requests\ForgotPasswordRequest;
use App\Services\ApiResponder;
use App\Services\Translator;
use Illuminate\Http\JsonResponse;
use Spatie\RouteAttributes\Attributes\Post;

final readonly class ForgotPasswordController
{
    public function __construct(private SendPasswordResetLinkAction $sendLink) {}

    #[Post('auth/forgot-password', middleware: 'throttle:password-reset-link')]
    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $this->sendLink->run($request->toEmail());

        // Один ответ для любой почты: есть ли такой аккаунт, не говорим
        return ApiResponder::ok(null, Translator::get('messages.auth.reset_link_sent'));
    }
}
