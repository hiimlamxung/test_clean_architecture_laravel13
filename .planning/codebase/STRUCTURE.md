# Codebase Structure

**Analysis Date:** 2026-05-10

## Directory Layout

```
Test_laravel13_1/
├── app/                            # Application code (PSR-4: App\)
│   ├── Actions/                    # Use-case classes (one method `handle()`)
│   │   ├── Auth/                   # `LoginAction`, `LogoutAction`
│   │   ├── Order/                  # `CreateOrderAction`
│   │   └── Payment/                # `InitiatePaymentAction`, `VerifyPaymentAction`, `HandlePaymentWebhookAction`
│   ├── Contracts/                  # Domain interfaces (DIP boundary)
│   │   └── Payment/                # `PaymentGateway` strategy contract
│   ├── DTO/                        # Immutable `final readonly` data carriers
│   │   ├── Auth/                   # `LoginData`, `TokenIssuedData`
│   │   ├── Order/                  # `CreateOrderData`
│   │   └── Payment/                # `InitiatePaymentData`, `PaymentInitiationResult`, `PaymentVerificationResult`, `WebhookResult`
│   ├── Enums/                      # PHP 8.3 string-backed enums
│   │   ├── OrderStatus.php
│   │   ├── PaymentMethod.php       # ALSO the strategy-resolution key
│   │   └── PaymentStatus.php
│   ├── Exceptions/                 # `HttpDomainException` base + subclasses by domain
│   │   ├── HttpDomainException.php
│   │   ├── Auth/                   # `InvalidCredentialsException`
│   │   └── Payment/                # `PaymentException`, `GatewayException`, `PaymentMethodNotSupportedException`
│   ├── Http/                       # HTTP edge
│   │   ├── Controllers/
│   │   │   ├── Controller.php      # base controller
│   │   │   └── Api/                # API controllers ONLY (no web controllers)
│   │   ├── Requests/               # FormRequests (validation + DTO builders)
│   │   └── Resources/              # API JSON resources (response shaping)
│   ├── Models/                     # Eloquent models — relations + casts only
│   ├── Policies/                   # Authorization predicates
│   ├── Providers/                  # Service container bindings
│   │   ├── AppServiceProvider.php  # currently empty stubs
│   │   └── PaymentServiceProvider.php  # binds `PaymentGatewayManager` singleton
│   └── Services/                   # Domain orchestrators / external integrations
│       └── Payment/
│           ├── Gateways/           # 4 strategies: Momo, Paypal, ZaloPay, BankTransfer
│           ├── PaymentGatewayManager.php  # strategy resolver
│           └── PaymentService.php  # orchestrator (transactions + state)
├── bootstrap/
│   ├── app.php                     # Application::configure(...) — routing + JSON exception render
│   ├── providers.php               # Provider list
│   └── cache/                      # Compiled config/routes/services (generated)
├── config/                         # Laravel config (single read site for `env()`)
│   └── payment.php                 # gateway credentials map
├── database/
│   ├── factories/                  # Order/Payment/User factories
│   ├── migrations/                 # users, cache, jobs, personal_access_tokens, orders, payments
│   └── seeders/                    # `DatabaseSeeder`
├── docker/
│   └── nginx/                      # nginx vhost / FastCGI config
├── public/                         # `index.php` web entry, static assets
├── resources/
│   ├── css/                        # Tailwind v4 entry
│   ├── js/                         # Vite-managed JS
│   └── views/                      # Blade — currently `welcome.blade.php`
├── routes/
│   ├── api.php                     # `/api/*` (Sanctum-protected groups)
│   ├── web.php                     # `/` welcome
│   └── console.php                 # closure commands
├── storage/                        # logs, framework caches, app uploads (gitignored)
├── tests/
│   ├── Feature/                    # End-to-end through HTTP/DB
│   │   ├── Auth/                   # LoginTest
│   │   ├── Order/                  # CreateOrderTest
│   │   └── Payment/                # InitiatePaymentTest, VerifyPaymentTest, HandleWebhookTest
│   ├── Unit/                       # Action / gateway / manager unit tests
│   └── TestCase.php
├── docker-compose-win-local.yml    # 5 services: nginx, app, mysql, redis, mailpit
├── Dockerfile.local                # PHP 8.3-fpm-alpine image build
├── composer.json                   # `^8.3` PHP, Laravel `^13.0`, Sanctum, Boost (dev)
├── package.json                    # Vite 8, Tailwind v4
├── phpunit.xml
├── vite.config.js
├── CLAUDE.md                       # Binding code rules — MUST READ before edits
└── README.md
```

## Directory Purposes

**`app/Actions/`:**
- Purpose: Application use-cases. One file = one class = one public method `handle()`.
- Contains: `final readonly` classes grouped into sub-namespaces by domain (`Auth/`, `Order/`, `Payment/`).
- Key files: `app/Actions/Payment/InitiatePaymentAction.php`, `app/Actions/Auth/LoginAction.php`, `app/Actions/Order/CreateOrderAction.php`.

**`app/Contracts/`:**
- Purpose: Project-owned interfaces used to invert dependencies for swappable infrastructure.
- Contains: Currently only `Payment/PaymentGateway.php`. Add new contracts here BEFORE writing the consumer.
- Key files: `app/Contracts/Payment/PaymentGateway.php`.

**`app/DTO/`:**
- Purpose: Strongly-typed immutable transports between layers — replaces loose arrays.
- Contains: `final readonly` classes with constructor-promoted public properties. Naming: `XxxData` for inputs, `XxxResult` for outputs.
- Key files: `app/DTO/Payment/InitiatePaymentData.php`, `app/DTO/Payment/PaymentInitiationResult.php`, `app/DTO/Auth/TokenIssuedData.php`.

**`app/Enums/`:**
- Purpose: Closed sets used everywhere instead of magic strings/ints. May carry behavior methods.
- Contains: PHP 8.3 string-backed enums.
- Key files: `app/Enums/PaymentMethod.php`, `app/Enums/PaymentStatus.php` (note `isFinal()`/`isSuccessful()` helpers), `app/Enums/OrderStatus.php`.

**`app/Exceptions/`:**
- Purpose: Domain failure signaling rendered to JSON via `bootstrap/app.php`.
- Contains: `HttpDomainException` base + subclasses grouped by domain.
- Key files: `app/Exceptions/HttpDomainException.php`, `app/Exceptions/Auth/InvalidCredentialsException.php`, `app/Exceptions/Payment/{PaymentException,GatewayException,PaymentMethodNotSupportedException}.php`.

**`app/Http/Controllers/Api/`:**
- Purpose: HTTP controllers — thin orchestration only.
- Contains: One controller per resource. `final` classes. Constructor-injected `Gate`. Method-injected Action + FormRequest.
- Key files: `app/Http/Controllers/Api/AuthController.php`, `OrderController.php`, `PaymentController.php`.

**`app/Http/Requests/`:**
- Purpose: Validation + DTO building. Controllers never read raw input.
- Contains: `final` FormRequests with `authorize()`, `rules()`, and either typed accessors (`->method()`, `->order()`) or `toData()`.
- Key files: `app/Http/Requests/InitiatePaymentRequest.php`, `LoginRequest.php`, `CreateOrderRequest.php`.

**`app/Http/Resources/`:**
- Purpose: Outbound JSON shaping.
- Contains: `final` JsonResource subclasses with `@mixin ModelType` annotation.
- Key files: `app/Http/Resources/PaymentResource.php`, `OrderResource.php`, `UserResource.php`, `TokenResource.php`.

**`app/Models/`:**
- Purpose: Eloquent persistence — relations, attribute casts (especially enum casts), `#[Fillable]` / `#[Hidden]` attributes. **No business logic.**
- Contains: `User`, `Order`, `Payment`.
- Key files: `app/Models/User.php`, `app/Models/Order.php`, `app/Models/Payment.php`.

**`app/Policies/`:**
- Purpose: Per-resource authorization predicates (Gate-resolved).
- Contains: `final` policy classes; method names match Gate ability strings (`view`, `pay`).
- Key files: `app/Policies/OrderPolicy.php`, `app/Policies/PaymentPolicy.php`.

**`app/Providers/`:**
- Purpose: Service container bindings.
- Contains: `AppServiceProvider` (currently empty), `PaymentServiceProvider` (registers `PaymentGatewayManager` singleton with the 4-strategy map).
- Key files: `app/Providers/PaymentServiceProvider.php`.

**`app/Services/`:**
- Purpose: Domain orchestrators + infrastructure adapters (e.g. external SDK wrappers).
- Contains: `app/Services/Payment/PaymentService.php` (orchestrator), `PaymentGatewayManager.php` (resolver), `Gateways/*Gateway.php` (strategies).
- Key files: `app/Services/Payment/PaymentService.php`, `app/Services/Payment/PaymentGatewayManager.php`, `app/Services/Payment/Gateways/{MomoGateway,PaypalGateway,ZaloPayGateway,BankTransferGateway}.php`.

**`bootstrap/`:**
- Purpose: Application wiring entry. `app.php` configures middleware / routing / exceptions; `providers.php` lists active providers.
- Generated subfolder: `bootstrap/cache/` (do NOT edit, gitignored).

**`config/`:**
- Purpose: The ONLY place allowed to call `env()`. Single source for runtime config.
- Key files: `config/payment.php` (gateway secrets map), `config/sanctum.php`, `config/database.php`, `config/queue.php`.

**`database/`:**
- Purpose: Schema + seeded test data.
- Contains: `migrations/` (timestamped), `factories/` (per-model), `seeders/`.

**`docker/`:**
- Purpose: Container build assets used by `docker-compose-win-local.yml`.
- Contains: `docker/nginx/` for the `tl13-nginx` service.

**`routes/`:**
- Purpose: HTTP + console route definitions.
- Key files: `routes/api.php` (Sanctum-protected business endpoints + public webhook), `routes/web.php` (welcome only), `routes/console.php`.

**`tests/`:**
- Purpose: PHPUnit test suite.
- Contains: `Feature/` (HTTP through DB), `Unit/` (Actions, gateways, manager). Mirrors `app/` namespace structure.

## Key File Locations

**Entry Points:**
- `public/index.php`: HTTP entry (called by nginx FastCGI).
- `bootstrap/app.php`: Application bootstrap, routing config, JSON exception renderer.
- `artisan`: CLI entry (must be run inside `tl13-app` container).

**Configuration:**
- `.env`: Runtime config (gitignored). Single source of truth — see `CLAUDE.md`.
- `.env.example`: Committed template.
- `config/payment.php`: Gateway credentials map (read by gateway implementations).
- `docker-compose-win-local.yml`: Service topology (nginx 8088, mysql 3315, redis 6382, mailpit 8025/1025).
- `composer.json`: PHP & Laravel version pin.
- `package.json` + `vite.config.js`: Frontend build (Tailwind v4 + Vite 8).
- `phpunit.xml`: Test runner config.

**Core Logic:**
- `app/Services/Payment/PaymentService.php`: Payment orchestrator (entry to write new payment workflows).
- `app/Services/Payment/PaymentGatewayManager.php`: Strategy resolver.
- `app/Providers/PaymentServiceProvider.php`: Where new gateways must be registered.
- `app/Actions/`: All use-cases live here.

**Testing:**
- `tests/Feature/`: HTTP-level tests per endpoint group.
- `tests/Unit/`: Per-class unit tests, mirrors `app/` namespace.
- `tests/TestCase.php`: Base test case.
- `database/factories/`: Required for any new model used in tests.

## Naming Conventions

**Files (one class per file, file name = class name):**
- Controllers: `XxxController.php` under `app/Http/Controllers/Api/` (e.g. `PaymentController.php`).
- FormRequests: `XxxRequest.php` (e.g. `InitiatePaymentRequest.php`, `CreateOrderRequest.php`).
- API Resources: `XxxResource.php` (e.g. `PaymentResource.php`, `TokenResource.php`).
- Actions: `XxxAction.php` with single public `handle()` (e.g. `InitiatePaymentAction.php`).
- Services: `XxxService.php` for orchestrators (e.g. `PaymentService.php`); manager classes use `XxxManager.php` (e.g. `PaymentGatewayManager.php`).
- Strategies / Gateways: `XxxGateway.php` (e.g. `MomoGateway.php`).
- DTOs: `XxxData.php` for inputs, `XxxResult.php` for outputs (e.g. `LoginData.php`, `PaymentInitiationResult.php`).
- Policies: `XxxPolicy.php` (e.g. `OrderPolicy.php`).
- Exceptions: `XxxException.php` (e.g. `GatewayException.php`).
- Enums: bare PascalCase noun (`PaymentStatus.php`, `OrderStatus.php`).

**Directories:**
- PascalCase, plural noun for collections: `Actions/`, `Models/`, `Resources/`, `Policies/`.
- Domain sub-namespace: PascalCase singular (`Auth/`, `Order/`, `Payment/`) — used inside `Actions/`, `DTO/`, `Exceptions/`.

**PHP class style (per `CLAUDE.md`):**
- Class = noun (`PaymentService`), method = verb (`handle()`, `initiate()`, `verify()`).
- `final readonly` for Actions, Services, DTOs, Gateways, GatewayManager.
- `final` (no `readonly`) for Controllers, FormRequests, Resources, Policies, Exceptions.
- `class` (no modifier) for Eloquent Models (Eloquent creates dynamic state).
- ALWAYS `declare(strict_types=1);` first line.

**Database conventions:**
- Migrations: timestamped (`YYYY_MM_DD_HHMMSS_create_xxx_table.php`).
- Tables: snake_case plural (`orders`, `payments`).
- Columns: snake_case (`order_id`, `gateway_reference`, `gateway_payload`, `paid_at`).

## Where to Add New Code

**New API endpoint (e.g. cancel an order):**
- Route: append to `routes/api.php` inside the `auth:sanctum` group.
- Controller method: extend the matching controller in `app/Http/Controllers/Api/` (e.g. add `cancel()` to `OrderController`).
- Validation: new FormRequest under `app/Http/Requests/` (e.g. `CancelOrderRequest.php`) with `rules()` and `toData()`.
- Use-case: new Action in `app/Actions/Order/` (e.g. `CancelOrderAction.php`) — `final readonly`, single `handle()`.
- Authorization: add an ability method to `app/Policies/OrderPolicy.php` (e.g. `cancel(User, Order)`).
- Response: reuse or extend `app/Http/Resources/OrderResource.php`.
- Tests: feature test in `tests/Feature/Order/`, unit test in `tests/Unit/Order/`.

**New domain (e.g. Refund):**
- Sub-namespace folders: create `app/Actions/Refund/`, `app/DTO/Refund/`, `app/Exceptions/Refund/` (only those you need).
- Service / orchestrator if multi-step: `app/Services/Refund/RefundService.php`.
- Bind any new contract in `app/Providers/AppServiceProvider::register()` or a dedicated provider added to `bootstrap/providers.php`.
- Enums (e.g. `RefundStatus`): `app/Enums/RefundStatus.php`.
- Exceptions: subclass `App\Exceptions\HttpDomainException` and set `$httpStatus`.

**New payment gateway (e.g. VNPay):**
- Add enum case: `case Vnpay = 'Vnpay';` in `app/Enums/PaymentMethod.php` (+ `label()` arm).
- Strategy: `app/Services/Payment/Gateways/VnpayGateway.php` implementing `App\Contracts\Payment\PaymentGateway`.
- Register: append `PaymentMethod::Vnpay->value => $app->make(VnpayGateway::class),` in `app/Providers/PaymentServiceProvider.php`.
- Config: add a `'Vnpay' => [...]` entry to `config/payment.php`.
- Tests: `tests/Unit/Payment/Gateways/VnpayGatewayTest.php` (mirror existing gateway tests).

**New Eloquent model:**
- Migration: `php artisan make:migration` inside the container.
- Model: under `app/Models/`, `class` only (no `final`/`readonly`), `#[Fillable([...])]`, `#[Hidden([...])]` if applicable, `casts()` for enum columns, relations as typed methods.
- Factory: `database/factories/XxxFactory.php`.
- Policy if user-owned: `app/Policies/XxxPolicy.php` (auto-discovered).
- Resource: `app/Http/Resources/XxxResource.php`.

**Utilities / shared helpers:**
- No `app/Helpers/`, no `app/Support/` exists today. Prefer dedicated value objects under `app/DTO/` or domain-specific service classes — do NOT introduce a generic dumping ground.

**New configuration value:**
- Put it in the relevant `config/*.php` file using `env('NAME', default)`.
- Update `.env.example`.
- Inject via `Illuminate\Contracts\Config\Repository` (NOT `config()` facade in Service/Action).

## Special Directories

**`bootstrap/cache/`:**
- Purpose: Compiled config/routes/services cache.
- Generated: Yes (by `php artisan optimize`).
- Committed: No (gitignored).

**`storage/`:**
- Purpose: Logs, framework caches, sessions, file uploads, compiled views.
- Generated: Yes.
- Committed: No (only `.gitkeep`s).

**`vendor/`:**
- Purpose: Composer dependencies.
- Generated: Yes (by `composer install` inside the container).
- Committed: No.

**`public/build/`:**
- Purpose: Vite-compiled frontend assets.
- Generated: Yes (by `npm run build`).
- Committed: No.

**`.planning/`:**
- Purpose: GSD planning artefacts (`codebase/` analyses, plans, etc.).
- Generated: By GSD commands.
- Committed: Decided per project.

---

*Structure analysis: 2026-05-10*
