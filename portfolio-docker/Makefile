# Layout: the compose files (docker-compose.yml / .prod.yml) and the app
# (composer.json, frontend/, public/, docker/) all live at the REPO ROOT.
# This Makefile lives in portfolio-docker/, so we `cd` to the repo root first.
# The env file stays in portfolio-docker/.
ROOT_DIR ?= ..
# Single env file: the repo-root .env (auto-loaded by Compose from the CWD after `cd $(ROOT_DIR)`).
COMPOSE      ?= cd $(ROOT_DIR) && docker compose
COMPOSE_PROD ?= cd $(ROOT_DIR) && docker compose -f docker-compose.prod.yml
export HOST_UID ?= $(shell id -u)
export HOST_GID ?= $(shell id -g)

.DEFAULT_GOAL := help
.PHONY: help init up down restart build logs ps sh db redis install migrate migration test stan lint fresh backup prod-build prod-up prod-migrate prod-logs prod-down deploy

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "  \033[36m%-14s\033[0m %s\n",$$1,$$2}'

# Repo root (where the single .env lives). All documented usage runs make from
# portfolio-docker/ (e.g. `cd portfolio-docker && make init`), so the repo root
# is the parent of the CWD. (shell pwd gives the true working directory.)
ROOT := $(abspath $(shell pwd)/..)

# init: Create the root .env (from .env.example) with random secrets.
# Credentials are stored as PARTS (MYSQL_USER/MYSQL_PASSWORD/MYSQL_DATABASE,
# REDIS_PASSWORD). DATABASE_URL / REDIS_URL / MESSENGER_TRANSPORT_DSN are DERIVED
# from those parts (via portfolio-docker/envgen.py) so the DSNs always match the
# credentials. In prod, docker-compose.prod.yml re-derives the SAME DSNs from the
# same parts and injects them into the container (the baked .env.dist has them
# empty, so Compose's values always win).
init: ## Create the root .env (from .env.example) with random secrets + derived DSNs
	@if [ -f $(ROOT)/.env ]; then \
	  echo ".env already exists at repo root (delete it first if you want a fresh one)"; \
	else \
	  cp $(ROOT)/.env.example $(ROOT)/.env; \
	  python3 $(ROOT)/portfolio-docker/envgen.py $(ROOT)/.env; \
	fi

up: ## Build and start the dev stack
	$(COMPOSE) up -d --build --wait --wait-timeout 300 --remove-orphans

down: ## Stop the dev stack
	$(COMPOSE) down --remove-orphans

restart: ## Restart php + messenger
	$(COMPOSE) restart php messenger

build: ## Build dev images
	$(COMPOSE) build

logs: ## Tail logs
	$(COMPOSE) logs -f --tail=100

ps: ## Show containers
	$(COMPOSE) ps

sh: ## Shell in the php container
	$(COMPOSE) exec php sh

db: ## MySQL client
	$(COMPOSE) exec mysql sh -c 'mysql -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" "$$MYSQL_DATABASE"'

redis: ## Redis CLI
	$(COMPOSE) exec redis sh -c 'redis-cli -a "$$REDIS_PASSWORD" --no-auth-warning'

install: ## composer install
	$(COMPOSE) exec php composer install

migrate: ## Run Doctrine migrations
	$(COMPOSE) exec php php bin/console doctrine:migrations:migrate --no-interaction

migration: ## Generate a migration from entity changes
	$(COMPOSE) exec php php bin/console doctrine:migrations:diff

test: ## PHPUnit
	$(COMPOSE) exec php php bin/phpunit

stan: ## PHPStan
	$(COMPOSE) exec php vendor/bin/phpstan analyse

lint: ## Frontend lint
	$(COMPOSE) exec node npm run lint

fresh: ## Wipe volumes and rebuild from scratch
	$(COMPOSE) down -v --remove-orphans
	$(MAKE) up

backup: ## Dump MySQL to backup-DATE.sql.gz
	$(COMPOSE) exec -T mysql sh -c 'mysqldump -u"$$MYSQL_USER" -p"$$MYSQL_PASSWORD" --single-transaction "$$MYSQL_DATABASE"' | gzip > backup-$$(date +%F).sql.gz

prod-build: ## Build production images
	$(COMPOSE_PROD) build

prod-up: ## Start production stack
	$(COMPOSE_PROD) up -d --build --wait --wait-timeout 300 --remove-orphans

prod-migrate: ## Run migrations in production (explicit deploy step)
	$(COMPOSE_PROD) run --rm php php bin/console doctrine:migrations:migrate --no-interaction

prod-logs: ## Tail production logs
	$(COMPOSE_PROD) logs -f --tail=100

prod-down: ## Stop production stack
	$(COMPOSE_PROD) down --remove-orphans

deploy: ## One-shot deploy (pull + build + migrate + up + health check)
	@bash $(ROOT)/portfolio-docker/deploy.sh
