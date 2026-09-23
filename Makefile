.PHONY: bootstrap build up down logs shell test format migrate seed status

bootstrap:
	cp .env.example .env
	docker compose build
	docker compose run --rm app php artisan key:generate
	docker compose run --rm app php artisan migrate --seed

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

logs:
	docker compose logs -f app worker scheduler

shell:
	docker compose exec app sh

test:
	docker compose run --rm -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e QUEUE_CONNECTION=sync app php artisan test

format:
	docker compose run --rm app ./vendor/bin/pint

migrate:
	docker compose run --rm app php artisan migrate

seed:
	docker compose run --rm app php artisan db:seed

status:
	docker compose run --rm app php artisan vira:status

