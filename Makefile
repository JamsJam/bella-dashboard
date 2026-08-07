# ==============================================================================
# ? Configuration des exécutables et options communes
# ==============================================================================
PHP ?= php
COMPOSER ?= composer
DEV_MEMORY_LIMIT ?= 512M
SYMFONY ?= symfony
CONSOLE := $(PHP) bin/console
PHP_CS_FIXER := vendor/bin/php-cs-fixer
PHPCS := vendor/bin/phpcs
PHPCBF := vendor/bin/phpcbf
PHPUNIT := $(PHP) bin/phpunit
BDI := vendor/bin/bdi

# * Le scan de style est non modifiant par défaut
DR ?= 1

.PHONY: dev test test-unit test-integration test-application test-e2e test-from-integration test-clothes test-clothes-unit test-clothes-integration test-clothes-application test-clothes-e2e test-avatar test-avatar-unit test-avatar-integration test-avatar-application test-avatar-e2e test-browser-install test-avatar-from-integration docker-test docker-test-avatar docker-test-up docker-test-prepare docker-test-run docker-test-avatar-run docker-test-down lint lint-container lint-translations lint-twig lint-xliff lint-yaml cs-scan

# ==============================================================================
# * ENVIRONNEMENT DE DÉVELOPPEMENT
# ==============================================================================
# ? Installe les dépendances, démarre Symfony et surveille les fichiers Sass
dev:
	COMPOSER_MEMORY_LIMIT=$(DEV_MEMORY_LIMIT) $(COMPOSER) install
	$(SYMFONY) serve:start -d
	$(SYMFONY) console sass:build --watch

# ==============================================================================
# * TESTS REGROUPÉS PAR NIVEAU
# ==============================================================================
# ? Lance tous les niveaux dans l’ordre, tous volets fonctionnels confondus
test: test-unit test-integration test-application test-e2e

# ? Toutes les règles isolées portant le groupe PHPUnit unit
test-unit:
	$(PHPUNIT) --group unit --display-all-issues

# ? Toutes les intégrations Symfony, Doctrine, Messenger et Workflow
test-integration:
	$(PHPUNIT) --group integration --display-all-issues

# ? Tous les parcours HTTP BrowserKit et DomCrawler
test-application:
	$(PHPUNIT) --group application --display-all-issues

# ? Tous les parcours dans un vrai navigateur Panther
test-e2e:
	$(PHPUNIT) --group end-to-end --display-all-issues

# ? Tous les niveaux nécessitant l’infrastructure, sans les tests unitaires
test-from-integration: test-integration test-application test-e2e

# ------------------------------------------------------------------------------
# * TESTS DU VOLET VÊTEMENTS
# ------------------------------------------------------------------------------
# ? Lance successivement les règles isolées, les intégrations puis les parcours HTTP
test-clothes: test-clothes-unit test-clothes-integration test-clothes-application test-clothes-e2e

# ? Tests unitaires sans kernel ni base de données
test-clothes-unit:
	$(PHPUNIT) tests/Clothes/Unit --display-all-issues

# ? Tests avec le vrai conteneur Symfony, Doctrine et Workflow
test-clothes-integration:
	$(PHPUNIT) tests/Clothes/Integration --display-all-issues

# ? Tests HTTP avec BrowserKit, DomCrawler, formulaires et MySQL test
test-clothes-application:
	$(PHPUNIT) tests/Clothes/Application --display-all-issues

# ? Parcours vêtements dans un vrai navigateur avec JavaScript, Turbo et Stimulus
test-clothes-e2e:
	$(PHPUNIT) tests/Clothes/EndToEnd --display-all-issues

# ------------------------------------------------------------------------------
# * TESTS DU VOLET AVATAR
# ------------------------------------------------------------------------------
# ? Lance les règles isolées, les intégrations, les parcours HTTP puis Panther
test-avatar: test-avatar-unit test-avatar-integration test-avatar-application test-avatar-e2e

# ? Tests unitaires sans kernel ni base de données
test-avatar-unit:
	$(PHPUNIT) tests/Avatar/Unit --display-all-issues

# ? Tests avec le vrai conteneur Symfony, Doctrine, Messenger et Workflow
test-avatar-integration:
	$(PHPUNIT) tests/Avatar/Integration --display-all-issues

# ? Tests HTTP avec BrowserKit, DomCrawler, sécurité et MySQL test
test-avatar-application:
	$(PHPUNIT) tests/Avatar/Application --display-all-issues

# ? Tests dans un vrai navigateur avec JavaScript, Turbo et Stimulus
test-avatar-e2e:
	$(PHPUNIT) tests/Avatar/EndToEnd --display-all-issues

# ? Détecte le navigateur local et installe le WebDriver compatible dans drivers/
test-browser-install:
	$(BDI) detect drivers --no-interaction

# ? Lance les niveaux nécessitant une infrastructure, sans les tests unitaires
test-avatar-from-integration: test-avatar-integration test-avatar-application test-avatar-e2e

# ------------------------------------------------------------------------------
# * INFRASTRUCTURE ET TESTS DANS DOCKER
# ------------------------------------------------------------------------------
# ? Construit l’image et démarre MySQL, Symfony et Selenium/Chromium
docker-test-up:
	docker compose --profile test build test-app test-runner
	docker compose --profile test up -d database test-browser test-app

# ? Crée la base MySQL de test et applique les migrations
docker-test-prepare:
	docker compose --profile test run --rm test-runner $(CONSOLE) doctrine:database:create --env=test --if-not-exists
	docker compose --profile test run --rm test-runner $(CONSOLE) doctrine:migrations:migrate --env=test --no-interaction

# ? Exécute tous les volets depuis l’intégration dans le conteneur PHP
docker-test-run:
	docker compose --profile test run --rm test-runner make test-from-integration

# ? Exécute uniquement le volet Avatar depuis l’intégration
docker-test-avatar-run:
	docker compose --profile test run --rm test-runner make test-avatar-from-integration

# ? Arrête les services de test sans supprimer les données MySQL de développement
docker-test-down:
	docker compose --profile test down

# ? Parcours Docker complet pour tous les volets ; lancer docker-test-down ensuite
docker-test: docker-test-up docker-test-prepare docker-test-run

# ? Parcours Docker complet limité au volet Avatar
docker-test-avatar: docker-test-up docker-test-prepare docker-test-avatar-run

# ==============================================================================
# * VALIDATION SYMFONY ET PRÉSENTATION
# ==============================================================================
# ? Lance tous les linters Symfony disponibles
lint: lint-container lint-translations lint-twig lint-xliff lint-yaml

# ? Vérifie la compilation du conteneur de services
lint-container:
	$(CONSOLE) lint:container

# ? Vérifie les templates Twig
lint-twig:
	$(CONSOLE) lint:twig templates

# ? Vérifie les fichiers de traduction XLIFF
lint-xliff:
	$(CONSOLE) lint:xliff translations

# ? Vérifie la configuration YAML Symfony
lint-yaml:
	$(CONSOLE) lint:yaml config --parse-tags

# ==============================================================================
# * QUALITÉ ET FORMATAGE DU CODE PHP
# ==============================================================================
# ? make cs-scan ou make cs-scan DR=1 analyse sans modifier les fichiers
# ! make cs-scan DR=0 applique automatiquement les corrections disponibles
cs-scan:
ifeq ($(DR),1)
	@fixer_status=0; phpcs_status=0; \
	$(PHP_CS_FIXER) fix --dry-run --diff --using-cache=no --sequential || fixer_status=$$?; \
	$(PHPCS) --standard=phpcs.xml.dist || phpcs_status=$$?; \
	test $$fixer_status -eq 0 -a $$phpcs_status -eq 0
else ifeq ($(DR),0)
	$(PHP_CS_FIXER) fix --using-cache=no --sequential
	@status=0; $(PHPCBF) --standard=phpcs.xml.dist || status=$$?; test $$status -le 1
	$(PHPCS) --standard=phpcs.xml.dist
else
	$(error DR doit valoir 0 ou 1)
endif
