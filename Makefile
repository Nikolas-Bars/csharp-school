# Команды для всех репозиториев сразу. У каждого репозитория свой Makefile (make help внутри).
# Runner — не сервис, а сборка: собирается до фронта (кладёт файлы в его public/runner),
# в up/down не участвует.
REPOS    = backend runner frontend
SERVICES = backend frontend

.DEFAULT_GOAL := help
.PHONY: help start up down check

help: ## Список команд
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

start: ## Первое разворачивание: бэкенд, сборка runner, фронт
	@for r in $(REPOS); do $(MAKE) --no-print-directory -C $$r start || exit 1; done

up: ## Поднять бэкенд и фронт
	@for r in $(SERVICES); do $(MAKE) --no-print-directory -C $$r up || exit 1; done

down: ## Остановить бэкенд и фронт
	@for r in $(SERVICES); do $(MAKE) --no-print-directory -C $$r down; done

check: ## Все проверки во всех репозиториях
	@for r in $(REPOS); do echo "== $$r"; $(MAKE) --no-print-directory -C $$r check || exit 1; done
