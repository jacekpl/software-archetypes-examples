.PHONY: up down build install demo

up:
	docker compose up -d

down:
	docker compose down -v

build:
	docker compose build

install:
	docker compose run --rm app npm install

demo:
	docker compose run --rm app npx ts-node src/index.ts
