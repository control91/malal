# Shortcuts for the Docker development stack (docker-compose.dev.yml).
COMPOSE = docker compose -f docker-compose.dev.yml
RUN = $(COMPOSE) exec app

.PHONY: up down logs shell artisan seed admin test lint reset

up:            ## Build and start everything (Ctrl+C to stop)
	$(COMPOSE) up --build

down:          ## Stop and remove containers (data is kept)
	$(COMPOSE) down

logs:          ## Follow the logs
	$(COMPOSE) logs -f app worker vite

shell:         ## Laravel Tinker
	$(RUN) php artisan tinker

artisan:       ## Any artisan command: make artisan cmd="route:list"
	$(RUN) php artisan $(cmd)

seed:          ## Base content + example data + one account per role
	$(RUN) php artisan app:seed-demo --with-users

admin:         ## Create a super admin: make admin email=vous@example.com
	$(COMPOSE) exec -it app php artisan app:create-admin --email=$(email)

test:          ## Test-suite (uses the malal_test database)
	$(COMPOSE) exec db sh -c "psql -U malal -tc \"SELECT 1 FROM pg_database WHERE datname='malal_laravel_test'\" | grep -q 1 || createdb -U malal malal_laravel_test"
	$(COMPOSE) exec -e DB_HOST=db -e DB_USERNAME=malal -e DB_PASSWORD=malal app ./vendor/bin/pest

lint:
	$(RUN) ./vendor/bin/pint --test

reset:         ## Delete containers AND volumes (database) — irreversible
	$(COMPOSE) down -v
