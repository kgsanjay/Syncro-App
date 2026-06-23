.PHONY: up down restart install migrate logs shell shell-db

# Default variables
DOCKER_COMPOSE = docker-compose

up:
	$(DOCKER_COMPOSE) up -d --build

down:
	$(DOCKER_COMPOSE) down

restart:
	$(DOCKER_COMPOSE) restart

install:
	$(DOCKER_COMPOSE) exec app composer install
	$(DOCKER_COMPOSE) exec app npm install

migrate:
	$(DOCKER_COMPOSE) exec app vendor/bin/phinx migrate -e development

logs:
	$(DOCKER_COMPOSE) logs -f

shell:
	$(DOCKER_COMPOSE) exec app sh

shell-db:
	$(DOCKER_COMPOSE) exec db mysql -u syncro_user -psyncro_password syncro_db
