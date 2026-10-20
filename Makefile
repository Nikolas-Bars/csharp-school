# .NET на Mac не нужен: всё идёт в контейнере SDK. Пакеты NuGet кэшируются в томе Docker
SDK_IMAGE      = mcr.microsoft.com/dotnet/sdk:10.0
DOTNET         = docker run --rm -v "$(CURDIR)":/src -v csharp-school-nuget:/root/.nuget/packages -w /src $(SDK_IMAGE) dotnet
DOTNET_TTY     = docker run --rm -it -v "$(CURDIR)":/src -v csharp-school-nuget:/root/.nuget/packages -w /src $(SDK_IMAGE)
PUBLISH_DIR    = artifacts/browser
FRONTEND_DIR   = ../csharp-school-frontend/public/runner

.DEFAULT_GOAL := help
.PHONY: help start build test format lint check shell clean

help: ## Список команд
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-8s\033[0m %s\n", $$1, $$2}'

start: build ## Первое разворачивание: то же, что build

build: ## Собрать runner в WebAssembly и положить во фронт (public/runner)
	rm -rf $(PUBLISH_DIR)
	$(DOTNET) publish src/Runner.Browser -c Release -o $(PUBLISH_DIR) --nologo
	rm -rf $(FRONTEND_DIR)
	mkdir -p $(FRONTEND_DIR)
	cp -R $(PUBLISH_DIR)/wwwroot/_framework $(FRONTEND_DIR)/_framework
	@echo "Runner: $$(du -sh $(FRONTEND_DIR) | cut -f1) в $(FRONTEND_DIR)"

test: ## Тесты ядра (компиляция, запуск, проверки) на обычном .NET
	$(DOTNET) test --nologo

format: ## Исправить стиль кода (dotnet format)
	$(DOTNET) format CSharpSchool.Runner.slnx

lint: ## Проверить стиль без исправлений
	$(DOTNET) format CSharpSchool.Runner.slnx --verify-no-changes

check: lint test ## Все проверки перед коммитом (сборка с предупреждениями-ошибками, стиль, тесты)

shell: ## Шелл в контейнере SDK
	$(DOTNET_TTY) bash

clean: ## Удалить результаты сборки
	rm -rf artifacts src/*/bin src/*/obj tests/*/bin tests/*/obj
