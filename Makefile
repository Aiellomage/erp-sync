# Comandi brevi per Docker (come i bin/* di markshust per Magento)
# Porte 8080/8443 per non andare in conflitto con Magento (80/443)

export HTTP_PORT  ?= 8080
export HTTPS_PORT ?= 8443
export HTTP3_PORT ?= 8443

DC      = docker compose
PHP     = $(DC) exec php
CONSOLE = $(PHP) bin/console

.DEFAULT_GOAL := help
.PHONY: help build up start down logs sh composer console test

help: ## Mostra i comandi disponibili
	@grep -E '^[a-z-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  make %-10s %s\n", $$1, $$2}'

build: ## Costruisce le immagini Docker
	$(DC) build --pull

up: ## Avvia i container e aspetta che siano pronti
	$(DC) up --wait

start: build up ## build + up

down: ## Ferma i container
	$(DC) down --remove-orphans

logs: ## Segue i log dei container
	$(DC) logs -f

sh: ## Apre una shell nel container PHP
	$(PHP) bash

composer: ## Esegue composer, es: make composer c="require symfony/orm-pack"
	$(PHP) composer $(c)

console: ## Esegue bin/console, es: make console c="debug:container"
	$(CONSOLE) $(c)

test: ## Esegue i test PHPUnit
	$(PHP) bin/phpunit
