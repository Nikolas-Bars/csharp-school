<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Провайдер каждого модуля лежит в app/Modules/{Module}/Providers и подключается сам:
     * новый модуль никуда прописывать не нужно.
     */
    public function register(): void
    {
        $files = glob(app_path('Modules/*/Providers/*ServiceProvider.php'));

        foreach ($files === false ? [] : $files as $file) {
            $module = basename(\dirname($file, 2));

            $this->app->register("App\\Modules\\{$module}\\Providers\\" . basename($file, '.php'));
        }
    }

    public function boot(): void
    {
        // Вне продакшена ошибкой становятся ленивая загрузка связей (скрытый N+1),
        // запись незаполняемых полей и чтение незагруженных атрибутов
        Model::shouldBeStrict(!App::isProduction());

        // Курсор страницы Laravel сам берёт из запроса и падает на подделанном (?cursor=e30 → 500).
        // Поэтому из запроса он не читается вовсе: список передаёт курсор явно, а разбирает и
        // проверяет его BaseRepository::cursor() — по колонкам своей сортировки
        CursorPaginator::currentCursorResolver(static fn(): null => null);
    }
}
