# Technology Stack

**Analysis Date:** 2026-05-10

## Languages

**Primary:**
- PHP ^8.3 (running 8.3-fpm-alpine inside container) — backend: `app/`, `bootstrap/`, `config/`, `routes/`, `database/`, `tests/`
- JavaScript (ES module) — frontend asset bundling: `vite.config.js`, `resources/js/`

**Secondary:**
- CSS (Tailwind v4) — styling: `resources/css/`
- Blade — server-side templating (Laravel default), under `resources/views/` if any

## Runtime

**Environment:**
- PHP-FPM 8.3 alpine (container `tl13-app`, exposes port 9000 internally) — see `Dockerfile.local`
- Node.js (Vite dev server / build) — host-side, version not pinned via `.nvmrc`
- Host PHP is 8.2 — per `CLAUDE.md`, composer **must not** run on host directly; always run inside the `app` container

**PHP extensions installed in container** (`Dockerfile.local`):
`pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd` (with freetype/jpeg), `intl`, `zip`, `opcache`, `redis` (PECL)

**Package Manager:**
- Composer 2.x — image `composer:2` copied into `Dockerfile.local`
- Lockfile: `composer.lock` present
- npm — `package.json` present, no lockfile committed (no `package-lock.json` observed at root)

## Frameworks

**Core:**
- Laravel Framework v13.7.0 (constraint `^13.0`) — `composer.lock` `laravel/framework`
- Laravel Sanctum v4.3.1 — API token / SPA auth: `config/sanctum.php`
- Laravel Tinker v3.0.2 — REPL, dev only

**Testing:**
- PHPUnit 11.5.55 — `phpunit.xml`
- Mockery 1.6.12 — mocks/spies in unit tests
- Faker (fakerphp/faker) v1.24.1 — model factories under `database/factories/`

**Build/Dev:**
- Vite ^8.0 — `vite.config.js`
- `laravel-vite-plugin` ^3.1 — Laravel Vite integration (with `bunny()` font helper for Instrument Sans)
- Tailwind CSS v4 via `@tailwindcss/vite` ^4.0
- `concurrently` ^9.0.1 — runs server/queue/logs/vite in parallel for `composer dev`
- Laravel Pint v1.29.1 — code formatter (PHP-CS-Fixer wrapper)
- Laravel Pail v1.2.6 — log tail tool
- Laravel Boost v2.4.6 (dev) — AI-assisted dev helper, `boost:install` not yet executed (per `CLAUDE.md`)
- Nuno Maduro Collision v8.9.4 — friendlier CLI error reporter
- Laravel Sail v1.58.0 — present in deps but **not** the active stack; project uses custom Docker compose instead

## Key Dependencies

**Critical:**
- `laravel/framework` v13.7.0 — application framework
- `laravel/sanctum` v4.3.1 — auth via personal access tokens (`auth:sanctum` middleware in `routes/api.php`)
- `guzzlehttp/guzzle` 7.10.0 — HTTP client (transitive via Laravel HTTP client)
- `monolog/monolog` 3.10.0 — logging backend
- `nesbot/carbon` 3.11.4 — date/time
- `symfony/console` v7.4.9 — Artisan command base

**Infrastructure:**
- `predis` / `phpredis` — Redis client (PHP `redis` PECL extension installed; `REDIS_CLIENT=phpredis` in `.env.example`)
- `pdo_mysql` extension — MySQL connectivity

## Configuration

**Environment:**
- Single source of truth: `.env` (gitignored). Compose file references vars via `${VAR:-default}` — see `docker-compose-win-local.yml`
- Example: `.env.example` (committed)
- Key env keys observed in `.env` (values redacted): `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_TIMEZONE`, `APP_PORT`, `APP_URL`, `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_CLIENT`, `MAIL_*`, `AWS_*`, `WWWUSER`, `WWWGROUP`, `FORWARD_DB_PORT`, `FORWARD_REDIS_PORT`, `FORWARD_MAILPIT_SMTP_PORT`, `FORWARD_MAILPIT_DASHBOARD_PORT`, `PAYMENT_MOCK_DEFAULT_STATUS`, `MOMO_WEBHOOK_SECRET`, `PAYPAL_WEBHOOK_SECRET`, `ZALOPAY_WEBHOOK_SECRET`, `BANK_WEBHOOK_SECRET`
- App config files: `config/app.php`, `config/auth.php`, `config/cache.php`, `config/database.php`, `config/filesystems.php`, `config/logging.php`, `config/mail.php`, `config/payment.php`, `config/queue.php`, `config/sanctum.php`, `config/services.php`, `config/session.php`

**Build:**
- `vite.config.js` — Vite + Laravel + Tailwind v4 + Bunny fonts (Instrument Sans 400/500/600)
- `phpunit.xml` — test runner config
- No `tsconfig.json`, no `.eslintrc*`, no `.prettierrc*` detected (frontend is minimal)
- No `.nvmrc` / `.python-version`

## Platform Requirements

**Development:**
- Docker Desktop on Windows (host)
- 5 containers via `docker-compose-win-local.yml`: `tl13-app`, `tl13-nginx`, `tl13-mysql`, `tl13-redis`, `tl13-mailpit`
- Network: `tl13` (bridge)
- Volumes: `mysql-data`, `redis-data`
- Ports forwarded to host (defaults): nginx `8088`, MySQL `3315`, Redis `6382`, Mailpit SMTP `1025`, Mailpit UI `8025`
- UID/GID synced via `WWWUSER` / `WWWGROUP` (default `1000:1000`) so volume-mounted files keep host ownership — see `Dockerfile.local` `usermod -u ${UID}` step
- Timezone fixed to UTC inside containers

**Production:**
- Not applicable — sandbox per `CLAUDE.md` ("Sandbox để thử Laravel 13 + Boost, không phải production")
- No CI/CD pipeline files (`.github/`, `.gitlab-ci.yml`, etc.) detected

---

*Stack analysis: 2026-05-10*
