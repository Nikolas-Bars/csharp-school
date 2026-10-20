COMPOSE = docker compose
EXEC    = $(COMPOSE) exec -T web

.DEFAULT_GOAL := help
.PHONY: help env start up down restart rebuild logs shell install lint lint-check format type-check test build check

help: ## Список команд
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

env: ## Создать .env из .env.example (если его ещё нет)
	@test -f .env || (cp .env.example .env && echo ".env создан")

start: env ## Собрать и поднять фронт одной командой
	$(COMPOSE) build
	$(COMPOSE) up -d --wait
	@echo ""
	@echo "  Фронт:  http://localhost:$${WEB_PORT:-5390}"
	@echo "  API:    $$(grep '^VITE_API_URL=' .env | cut -d= -f2) (поднимается отдельно: make start в csharp-school-backend)"
	@echo ""

up: env ## Поднять без пересборки
	$(COMPOSE) up -d --wait

down: ## Остановить
	$(COMPOSE) down

restart: down up ## Перезапустить

rebuild: ## Пересобрать с нуля (после изменения package.json)
	$(COMPOSE) down -v
	$(COMPOSE) build --no-cache
	$(COMPOSE) up -d --wait

logs: ## Логи dev-сервера
	$(COMPOSE) logs -f web

shell: ## Шелл в контейнере
	$(COMPOSE) exec web sh

install: ## npm install внутри контейнера (пример: make install p=axios)
	$(EXEC) npm install $(p)

lint: ## oxlint + eslint с автоисправлением
	$(EXEC) npm run lint

lint-check: ## oxlint + eslint + prettier без исправлений (как в CI)
	$(EXEC) npm run lint-check

format: ## Prettier
	$(EXEC) npm run format

type-check: ## Проверка типов vue-tsc
	$(EXEC) npm run type-check

test: ## Unit-тесты (vitest)
	$(EXEC) npm test

build: ## Production-сборка в dist/
	$(EXEC) npm run build

check: lint-check type-check test ## Всё перед коммитом (ничего не исправляет, как CI)
