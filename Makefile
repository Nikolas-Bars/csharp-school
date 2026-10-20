# Команды для всех репозиториев сразу. У каждого репозитория свой Makefile (make help внутри).
# Runner — не сервис, а сборка: собирается до фронта (кладёт файлы в его public/runner),
# в up/down не участвует.
REPOS    = backend runner frontend
SERVICES = backend frontend

# Production-стек (deploy/README.md): настройки и секреты — deploy/.env
PROD = docker compose -f deploy/docker-compose.prod.yml

.DEFAULT_GOAL := help
.PHONY: help start up down check \
        prod-build prod-migrate prod-up prod-deploy prod-down prod-ps prod-logs prod-check

help: ## Список команд
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-13s\033[0m %s\n", $$1, $$2}'

start: ## Первое разворачивание: бэкенд, сборка runner, фронт
	@for r in $(REPOS); do $(MAKE) --no-print-directory -C $$r start || exit 1; done

up: ## Поднять бэкенд и фронт
	@for r in $(SERVICES); do $(MAKE) --no-print-directory -C $$r up || exit 1; done

down: ## Остановить бэкенд и фронт
	@for r in $(SERVICES); do $(MAKE) --no-print-directory -C $$r down; done

check: ## Все проверки во всех репозиториях
	@for r in $(REPOS); do echo "== $$r"; $(MAKE) --no-print-directory -C $$r check || exit 1; done

prod-build: ## Production: собрать образы (тег — IMAGE_TAG в deploy/.env)
	$(PROD) build

prod-migrate: ## Production: миграции и загрузка курса (до prod-up)
	$(PROD) run --rm api php artisan migrate --force
	$(PROD) run --rm api php artisan course:import

prod-up: ## Production: запустить или обновить стек до образов IMAGE_TAG
	$(PROD) up -d --wait --remove-orphans

prod-deploy: prod-build prod-migrate prod-up ## Production: сборка, миграции и курс, запуск

prod-down: ## Production: остановить (данные в томах остаются)
	$(PROD) down

prod-ps: ## Production: состояние сервисов
	$(PROD) ps

prod-logs: ## Production: журналы (s=api — одного сервиса)
	$(PROD) logs -f --tail 100 $(s)

prod-check: ## Проверить production-образы локально: отдельный проект, порт 15390, потом всё удаляется
	deploy/check.sh
