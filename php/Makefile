.PHONY: up down build migrate demo diff shell

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

install:
	docker compose run --rm app composer install

migrate:
	docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction

diff:
	docker compose run --rm app php bin/console doctrine:migrations:diff

demo: migrate
	docker compose run --rm app php bin/console app:demo

shell:
	docker compose run --rm app sh
