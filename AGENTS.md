# Repository Guidelines

## Project Structure & Module Organization
This Symfony 7 API lives under `src/`, with HTTP entrypoints in `src/Controller`, Doctrine aggregates in `src/Entity`, and data access in `src/Repository`. Shared service wiring sits in `config/`, while environment overrides belong in `config/packages/` and `config/routes.yaml`. Database migrations are versioned in `migrations/`, public assets and the front controller live in `public/`, and Docker-specific bootstrap files live under `frankenphp/`. Use `bin/console` for framework tooling; runtime caches stay in `var/`. Architecture notes and design docs are collected in `docs/`.

## Build, Test, and Development Commands
Install PHP dependencies with `composer install`. Start the full stack via `docker compose up --wait`, then reach the API at `https://localhost`. Apply schema changes using `docker compose exec php bin/console doctrine:migrations:migrate`. Shut everything down with `docker compose down --remove-orphans`. During active work, `docker compose exec php bin/console cache:clear` keeps caches synced.

## Coding Style & Naming Conventions
Follow PSR-12 and the repo’s `.editorconfig`: four-space indentation, LF endings, UTF-8 encoding. Controllers are suffixed with `Controller`, Doctrine models with singular entity names, and repositories with `Repository`. Prefer promoted constructor properties, typed fields, and guard clauses for validation. Keep route names kebab-cased (`task-list`) and services snake-cased in YAML. Run `composer dump-autoload` when adding namespaces.

## Testing Guidelines
A dedicated `tests/` tree is expected next to `src/`; mirror the namespace and suffix files with `Test.php` (e.g., `Tests\\Controller\\TaskControllerTest.php`). Use PHPUnit inside the container: `docker compose exec php ./vendor/bin/phpunit`. Aim for integration coverage on controllers plus repository-level unit tests, and record fixtures under `tests/Fixtures/`. Failing tests should block merges; document deliberate skips in the relevant test class.

## Commit & Pull Request Guidelines
Commits follow a conventional `<type>: <summary>` style (`feat: add task filtering`). Group related changes; avoid mixing refactors and features. Reference tickets in parentheses when relevant (`(#123)`) and keep body lines wrapped at ~72 chars. Pull requests need a concise summary, linked issue, migration notes, and before/after evidence (response samples or screenshots). Include test output whenever behavior changes.

## Environment & Configuration
Baseline environment variables live in `.env`; never commit secrets—use `.env.local` or `compose.env` instead. Update connection strings in `DATABASE_URL` before running migrations. Caddy and FrankenPHP tweaks belong in `frankenphp/conf.d/`. When adding new config files, align them with the existing package-based folder layout so overrides stay discoverable.
