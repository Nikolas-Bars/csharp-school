<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Истёкшие токены доступа удаляются раз в сутки (срок жизни — SANCTUM_TOKEN_EXPIRATION)
Schedule::command('sanctum:prune-expired --hours=24')->daily()->onOneServer();

// Истёкшие ссылки сброса пароля (срок — auth.passwords.users.expire): в базе не копятся почты
Schedule::command('auth:clear-resets')->hourly()->onOneServer();
