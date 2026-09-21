# Учебное окружение: nginx + php-fpm + PostgreSQL

# Подтягиваем .env как переменные make (нужно для push и для отображения значений).
# Docker Compose сам читает .env для подстановки в compose.yaml — это отдельно, для Makefile.
ifneq (,$(wildcard .env))
include .env
export
endif

.DEFAULT_GOAL := help
.PHONY: help init build up down ps logs clean composer sh psql push

help:
	@echo "make init      — создать .env из .env.example (с UID/GID текущего пользователя)"
	@echo "make build     — собрать образы"
	@echo "make up        — поднять контейнеры (в фоне)"
	@echo "make down      — остановить и удалить контейнеры"
	@echo "make ps        — статус контейнеров"
	@echo "make logs      — логи всех сервисов (follow)"
	@echo "make clean     — down -v: остановить и удалить volume pgdata (данные будут потеряны)"
	@echo "make composer  — composer install внутри php-контейнера"
	@echo "make sh        — shell в php-контейнере"
	@echo "make psql      — консоль psql к postgres"
	@echo "make push      — собрать и запушить образы в Docker Hub (amd64+arm64)"

# Создаёт .env из примера и подставляет реальные UID/GID текущего пользователя,
# чтобы файлы, которые php-fpm пишет в bind mount (например app/vendor), на Linux-хосте
# принадлежали не root/произвольному системному uid, а текущему пользователю
init:
	@if [ -f .env ]; then \
		echo ".env уже существует, пропускаю"; \
	else \
		cp .env.example .env; \
		CURRENT_UID=$$(id -u); \
		CURRENT_GID=$$(id -g); \
		sed -i.bak "s/^UID=.*/UID=$$CURRENT_UID/" .env; \
		sed -i.bak "s/^GID=.*/GID=$$CURRENT_GID/" .env; \
		rm -f .env.bak; \
		echo ".env создан (UID=$$CURRENT_UID, GID=$$CURRENT_GID)"; \
	fi

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

ps:
	docker compose ps

logs:
	docker compose logs -f

clean:
	docker compose down -v

# Ставим зависимости от имени www-data — тогда vendor/ на хосте получит
# владельца из UID/GID, заданных при `make init`, а не root
composer:
	docker compose exec -u www-data php composer install

sh:
	docker compose exec php sh

psql:
	docker compose exec postgres psql -U $(POSTGRES_USER) -d $(POSTGRES_DB)

# Мультиархитектурная сборка и публикация в Docker Hub.
# Нужен предварительный `docker login` и заполненный DOCKERHUB_NAMESPACE в .env.
push:
	@test -n "$(DOCKERHUB_NAMESPACE)" || { echo "DOCKERHUB_NAMESPACE не задан — заполните .env"; exit 1; }
	docker buildx build --platform linux/amd64,linux/arm64 \
		-t $(DOCKERHUB_NAMESPACE)/devops-course-nginx:$(IMAGE_TAG) \
		--push ./docker/nginx
	docker buildx build --platform linux/amd64,linux/arm64 \
		-t $(DOCKERHUB_NAMESPACE)/devops-course-php:$(IMAGE_TAG) \
		--push ./docker/php
	docker buildx build --platform linux/amd64,linux/arm64 \
		-t $(DOCKERHUB_NAMESPACE)/devops-course-postgres:$(IMAGE_TAG) \
		--push ./docker/postgres
