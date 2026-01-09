# Repository Guidelines

## Project Structure & Module Organization
This Symfony 7.3 API (running on **FrankenPHP**) lives under `src/`, with HTTP entrypoints in `src/Controller`, Doctrine aggregates in `src/Entity`, and data access in `src/Repository`.
- **Auth**: **Lexik JWT Authentication** & Refresh Token Bundle.
- **Docs**: **Nelmio API Doc** (Swagger UI at `/api/doc`).
- **Config**: Shared service wiring sits in `config/`, environment overrides in `config/packages/`.
- **Migrations**: Versioned in `migrations/`.
- **Docker**: Bootstrap files live under `frankenphp/`.

## Build, Test, and Development Commands
Install PHP dependencies with `composer install`. Start the full stack via `docker compose up --wait`, then reach the API at `https://localhost` (or `https://localhost:8443` depending on port mapping).
- **Migrations**: `docker compose exec php bin/console doctrine:migrations:migrate`
- **Generate Fake Data**: `docker compose exec php bin/console app:dev:seed-random-data --purge`
- **Cache**: `docker compose exec php bin/console cache:clear`
- **Stop**: `docker compose down --remove-orphans`

## Coding Style & Naming Conventions
Follow PSR-12 and the repo’s `.editorconfig`.
- **Controllers**: Suffixed with `Controller`.
- **Entities**: Singular names.
- **Repositories**: Suffixed with `Repository`.
- **Properties**: Typed fields, promoted constructor properties.
- **Routes**: Kebab-cased (`task-list`).
- **Services**: Snake-cased in YAML.

## Testing Guidelines
A dedicated `tests/` tree exists next to `src/` with integration and unit tests.
- **Setup Test Environment**: 
  - `docker compose exec php bin/console doctrine:database:create --env=test`
  - `docker compose exec php bin/console doctrine:schema:create --env=test`
  - `docker compose exec php bin/console app:test:create-user --env=test`
- **Run Tests**: `docker compose exec php ./vendor/bin/phpunit`
- **Run Single Test**: `docker compose exec php ./vendor/bin/phpunit --filter TestMethodName`
- **Coverage**: Integration tests for controllers + unit tests for voters and services.

## Commit & Pull Request Guidelines
- **Commits**: `<type>: <summary>` style (`feat: add task filtering`).
- **PRs**: Concise summary, linked issue, migration notes, evidence (screenshots/responses).

## Environment & Configuration
- **.env**: Baseline variables.
- **Secrets**: Use `.env.local` or `compose.env`.
- **Server**: FrankenPHP configuration is in `frankenphp/conf.d/`.
