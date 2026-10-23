# Shortcuts for the Docker development stack (docker-compose.dev.yml).
COMPOSE = docker compose -f docker-compose.dev.yml
RUN = $(COMPOSE) exec web

.PHONY: up down stop logs shell manage seed admin test lint reset

up:            ## Build and start everything (Ctrl+C to stop)
	$(COMPOSE) up --build

down:          ## Stop and remove containers (data is kept)
	$(COMPOSE) down

logs:          ## Follow the logs
	$(COMPOSE) logs -f web worker assets

shell:         ## Django shell
	$(RUN) python manage.py shell

manage:        ## Any manage.py command: make manage cmd="showmigrations"
	$(RUN) python manage.py $(cmd)

seed:          ## Base content + example data + one account per role
	$(RUN) python manage.py seed_demo --with-users

admin:         ## Create a super admin: make admin email=vous@example.com
	$(COMPOSE) exec -it web python manage.py create_admin --email $(email)

test:          ## Test-suite inside the container
	$(RUN) python -m pytest

lint:
	$(RUN) ruff check .

reset:         ## Delete containers AND volumes (database, media) — irreversible
	$(COMPOSE) down -v
