DOCKER_COMPOSE = docker compose
APP_CONTAINER  = dmed-app

.PHONY: help build up down restart logs shell migrate fresh test cache composer artisan setup

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

# ── Container Lifecycle ───────────────────────────────────────

build: ## Build all containers
	$(DOCKER_COMPOSE) build

up: ## Start all services
	$(DOCKER_COMPOSE) up -d

down: ## Stop all services
	$(DOCKER_COMPOSE) down

restart: ## Restart all services
	$(DOCKER_COMPOSE) restart

logs: ## View logs (follow). Usage: make logs s=app
	$(DOCKER_COMPOSE) logs -f $(s)

# ── Development ───────────────────────────────────────────────

shell: ## Open shell in app container
	docker exec -it $(APP_CONTAINER) sh

# ── Laravel Commands ──────────────────────────────────────────

migrate: ## Run database migrations
	docker exec -it $(APP_CONTAINER) php artisan migrate --force

fresh: ## Fresh migrations with seed
	docker exec -it $(APP_CONTAINER) php artisan migrate:fresh --seed --force

test: ## Run tests
	docker exec -it $(APP_CONTAINER) php artisan test

cache: ## Clear and rebuild all caches
	docker exec -it $(APP_CONTAINER) php artisan config:clear
	docker exec -it $(APP_CONTAINER) php artisan route:clear
	docker exec -it $(APP_CONTAINER) php artisan view:clear
	docker exec -it $(APP_CONTAINER) php artisan event:clear
	docker exec -it $(APP_CONTAINER) php artisan config:cache
	docker exec -it $(APP_CONTAINER) php artisan route:cache
	docker exec -it $(APP_CONTAINER) php artisan view:cache
	docker exec -it $(APP_CONTAINER) php artisan event:cache

composer: ## Run composer. Usage: make composer c="require package/name"
	docker exec -it $(APP_CONTAINER) composer $(c)

artisan: ## Run artisan. Usage: make artisan c="make:model Foo"
	docker exec -it $(APP_CONTAINER) php artisan $(c)

# ── Initial Setup ─────────────────────────────────────────────

setup: ## Full initial setup
	@echo "── Building containers ──"
	$(DOCKER_COMPOSE) build
	@echo "── Starting services ──"
	$(DOCKER_COMPOSE) up -d
	@echo "── Waiting for services ──"
	sleep 10
	@echo "── Generating application key ──"
	docker exec -it $(APP_CONTAINER) php artisan key:generate --force
	@echo "── Running migrations ──"
	docker exec -it $(APP_CONTAINER) php artisan migrate --force
	@echo "── Creating storage link ──"
	docker exec -it $(APP_CONTAINER) php artisan storage:link
	@echo "── Creating RustFS bucket ──"
	docker exec -it dmed-rustfs mc alias set local http://localhost:9000 $${AWS_ACCESS_KEY_ID:-minio} $${AWS_SECRET_ACCESS_KEY:-minio123} 2>/dev/null || true
	docker exec -it dmed-rustfs mc mb local/$${AWS_BUCKET:-dmed} --ignore-existing 2>/dev/null || true
	@echo "── Setup complete ──"
