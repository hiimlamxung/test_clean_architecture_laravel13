# External Integrations

**Analysis Date:** 2026-05-10

## APIs & External Services

**Payment Gateways (all mocked, real-shape):**
Each gateway implements `App\Contracts\Payment\PaymentGateway` (`app/Contracts/Payment/PaymentGateway.php`) with three operations: `initiate()`, `verify()`, `parseWebhook()`. Resolution is via `App\Services\Payment\PaymentGatewayManager` (`app/Services/Payment/PaymentGatewayManager.php`), wired in `App\Providers\PaymentServiceProvider` (`app/Providers/PaymentServiceProvider.php`). Selection key is the `App\Enums\PaymentMethod` enum (`app/Enums/PaymentMethod.php`).

- **MoMo** — `App\Services\Payment\Gateways\MomoGateway` (`app/Services/Payment/Gateways/MomoGateway.php`)
  - SDK/Client: none — mock returns `https://mock.momo.local/pay/{ref}`
  - Auth: `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY`, `MOMO_WEBHOOK_SECRET`
  - Return URL env: `MOMO_RETURN_URL` (default `http://localhost:8088/payments/momo/return`)
  - Reference format: `MOCK-MOMO-{ULID base32}`

- **PayPal** — `App\Services\Payment\Gateways\PaypalGateway` (`app/Services/Payment/Gateways/PaypalGateway.php`)
  - SDK/Client: none — mock returns `https://mock.paypal.local/checkout/{ref}`
  - Auth: `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_MODE` (default `sandbox`), `PAYPAL_WEBHOOK_SECRET`
  - Return URL env: `PAYPAL_RETURN_URL` (default `http://localhost:8088/payments/paypal/return`)
  - Reference format: `MOCK-PAYPAL-{ULID base32}`

- **ZaloPay** — `App\Services\Payment\Gateways\ZaloPayGateway` (`app/Services/Payment/Gateways/ZaloPayGateway.php`)
  - SDK/Client: none — mock returns `https://mock.zalopay.local/order/{ref}`
  - Auth: `ZALOPAY_APP_ID`, `ZALOPAY_KEY1`, `ZALOPAY_KEY2`, `ZALOPAY_WEBHOOK_SECRET`
  - Return URL env: `ZALOPAY_RETURN_URL` (default `http://localhost:8088/payments/zalopay/return`)
  - Reference format: `MOCK-ZALO-{ULID base32}`

- **BankTransfer (manual)** — `App\Services\Payment\Gateways\BankTransferGateway` (`app/Services/Payment/Gateways/BankTransferGateway.php`)
  - SDK/Client: none — produces a QR string `BANK:{bank}|ACC:{acc}|NAME:{name}|AMOUNT:{amt}|MEMO:{ref}`, no `redirect_url`
  - Config: `BANK_NAME` (default `Vietcombank`), `BANK_ACCOUNT_NO` (default `1234567890`), `BANK_ACCOUNT_NAME` (default `CONG TY MOCK`), `BANK_WEBHOOK_SECRET`
  - `verify()` always returns `PaymentStatus::Pending` — only the webhook (admin confirm) advances the state
  - Reference format: `BANK-{ULID base32}`

**Mock control:**
`PAYMENT_MOCK_DEFAULT_STATUS` env (default `PaymentStatus::Succeeded->value`) — controls what `verify()` returns for Momo/Paypal/ZaloPay. Change to `Failed` or `Pending` to test branches. See `config/payment.php`.

**Email:**
- **Mailpit** (dev mail catcher) — container `tl13-mailpit`, image `axllent/mailpit:latest`
  - SMTP host (in compose network): `mailpit:1025`; UI on host port `${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}` (`http://localhost:8025`)
  - Default mailer in `.env.example` is `log` (writes to log channel) — flip `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025` to route through Mailpit
  - Mail config: `config/mail.php`

**Other third-party providers (defined in `config/services.php`, NOT configured/used):**
Postmark, Resend, AWS SES, Slack notifications — keys placeholder-only, no live integration.

## Data Storage

**Databases:**
- **MySQL 8.0** (primary) — container `tl13-mysql`
  - Internal: `mysql:3306`; host: `${FORWARD_DB_PORT:-3315}`
  - Auth plugin: `caching_sha2_password`; charset `utf8mb4` / `utf8mb4_unicode_ci`; tz `+00:00`
  - Connection: `mysql` driver in `config/database.php`
  - Env: `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`
  - Healthcheck: `mysqladmin ping` every 5s, retries 20 — `app` service waits on `service_healthy`
  - Volume: `mysql-data`
- **SQLite** — only the framework default in `config/database.php`; not used for this Docker setup

**Caching:**
- **Redis 7-alpine** — container `tl13-redis`
  - Internal: `redis:6379`; host: `${FORWARD_REDIS_PORT:-6382}`
  - Client: `phpredis` (`REDIS_CLIENT=phpredis`)
  - Used for cache + session + queue per `CLAUDE.md` (depending on env: `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`)
  - Default cache prefix: `Str::slug(APP_NAME).'-cache-'` (`config/cache.php`)
  - Volume: `redis-data`
- Note: stock `.env.example` defaults `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` — actual `.env` may switch these to `redis`

**File Storage:**
- Local filesystem only — `FILESYSTEM_DISK=local` (`config/filesystems.php`)
- Disks: `local` (`storage/app/private`), `public` (`storage/app/public`), `s3` (configured but no AWS credentials wired in `.env.example`)

## Authentication & Identity

**Auth Provider:**
- **Laravel Sanctum** v4.3.1 — `config/sanctum.php`
  - Implementation: personal access tokens via Bearer header (`auth:sanctum` middleware in `routes/api.php`)
  - Login: `POST /api/auth/login` → `App\Http\Controllers\Api\AuthController@login`
  - Logout: `POST /api/auth/logout` (auth required) → `AuthController@logout`
  - User probe: `GET /api/user` returns `$request->user()`
  - Stateful domains default: `localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1` (override via `SANCTUM_STATEFUL_DOMAINS`)
  - Token expiration: `null` (never expires) — overridable via Sanctum config
  - Token prefix: configurable via `SANCTUM_TOKEN_PREFIX` (empty by default)
- Password hashing: bcrypt, `BCRYPT_ROUNDS=12`
- Session driver: `database` per `.env.example` (override-able in `.env`)
- Default web guard: session/eloquent — `config/auth.php`

## Monitoring & Observability

**Error Tracking:**
- None (no Sentry / Bugsnag / Rollbar packages in `composer.json`)
- Custom domain exception rendering hook in `bootstrap/app.php` via `withExceptions()`:
  - `App\Exceptions\HttpDomainException` is rendered through its own `render(Request)` method
  - API routes (`api/*`) always render JSON regardless of `Accept` header

**Logs:**
- Laravel logging via Monolog 3.10 — `config/logging.php`
- Default: `LOG_CHANNEL=stack` → stack of `single` (file at `storage/logs/laravel.log`)
- Local viewer: Laravel Pail (`php artisan pail`) — included in the `composer dev` concurrently script

## CI/CD & Deployment

**Hosting:**
- Local Docker only (Windows host); no production deploy target

**CI Pipeline:**
- None detected (no `.github/workflows`, no `.gitlab-ci.yml`, no `Jenkinsfile`)

## Environment Configuration

**Required env vars (host → container ports):**
- `APP_PORT` (default `8088`) — nginx host port
- `APP_TIMEZONE` (default `UTC`)
- `WWWUSER` / `WWWGROUP` (default `1000` / `1000`) — UID/GID sync for volume mounts
- `FORWARD_DB_PORT` / `FORWARD_REDIS_PORT` / `FORWARD_MAILPIT_SMTP_PORT` / `FORWARD_MAILPIT_DASHBOARD_PORT`
- `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` / `DB_ROOT_PASSWORD` — MySQL bootstrap

**Required app env vars:**
- `APP_KEY` (generate via `php artisan key:generate`)
- `APP_URL` (e.g., `http://localhost:8088`)
- Payment gateway secrets: `MOMO_*`, `PAYPAL_*`, `ZALOPAY_*`, `BANK_*` — defaults provided in `config/payment.php` are mock-safe

**Secrets location:**
- `.env` only (gitignored). Per `CLAUDE.md`: "kiểm tra `.env` không có secret thật" before commits — file is committed-safe by being ignored.
- No external secret manager (Vault / AWS Secrets Manager / etc.) integrated.

## Webhooks & Callbacks

**Incoming:**
- `POST /api/payments/webhooks/{method}` — `App\Http\Controllers\Api\PaymentController@webhook` (`routes/api.php` line 26)
  - Public route (no `auth:sanctum`)
  - `{method}` value matches `PaymentMethod` enum (`Momo`, `Paypal`, `ZaloPay`, `BankTransfer`)
  - Signature verification: each gateway's `parseWebhook()` compares header `X-Mock-Signature` (or lowercase) against the gateway's `webhook_secret` env using `hash_equals()`; throws `App\Exceptions\Payment\GatewayException` on mismatch
  - Payload contract: requires `gateway_reference` (string) + `status` (must parse to `PaymentStatus` enum); else throws `Malformed webhook payload`

**Outgoing:**
- Gateway redirect URLs (initiation step) — currently mock URLs only:
  - `https://mock.momo.local/pay/{ref}`
  - `https://mock.paypal.local/checkout/{ref}`
  - `https://mock.zalopay.local/order/{ref}`
- Return URLs (post-payment redirects) per gateway: `MOMO_RETURN_URL`, `PAYPAL_RETURN_URL`, `ZALOPAY_RETURN_URL` (defaults under `http://localhost:8088/payments/{gw}/return`)
- No outgoing webhook callers (no event broadcasting to third parties).

---

*Integration audit: 2026-05-10*
