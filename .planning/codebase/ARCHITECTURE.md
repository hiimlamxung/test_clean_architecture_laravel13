<!-- refreshed: 2026-05-10 -->
# Architecture

**Analysis Date:** 2026-05-10

## System Overview

```text
┌─────────────────────────────────────────────────────────────┐
│                       HTTP Edge (nginx)                      │
│         `docker/nginx/*` → FastCGI → app:9000 (PHP-FPM)     │
└──────────────────────────┬──────────────────────────────────┘
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    Routing & Middleware                      │
│   `bootstrap/app.php` · `routes/api.php` · `routes/web.php`  │
│        Sanctum auth groups · JSON-only error renderer        │
└──────────────────────────┬──────────────────────────────────┘
                           ▼
┌──────────────────┬──────────────────┬───────────────────────┐
│   Controllers    │   FormRequests   │       Policies        │
│ `app/Http/       │ `app/Http/       │   `app/Policies/`     │
│  Controllers/    │  Requests/`      │ OrderPolicy /         │
│  Api/`           │ validate +       │ PaymentPolicy         │
│ thin orchestrate │ build DTO        │ (Gate::authorize)     │
└────────┬─────────┴────────┬─────────┴──────────┬────────────┘
         │                  │                     │
         ▼                  ▼                     ▼
┌─────────────────────────────────────────────────────────────┐
│                   Application (Use-Cases)                    │
│              `app/Actions/{Auth,Order,Payment}/`             │
│     1 file = 1 class = 1 method `handle()`, DI via ctor      │
│   Inputs: DTOs from `app/DTO/{Auth,Order,Payment}/`          │
└──────────────────────────┬──────────────────────────────────┘
                           ▼
┌─────────────────────────────────────────────────────────────┐
│             Domain Service / Orchestrator                    │
│       `app/Services/Payment/PaymentService.php`              │
│   Coordinates gateway + persistence + DB::transaction()      │
└────────────┬─────────────────────────────────┬──────────────┘
             ▼                                 ▼
┌────────────────────────────┐  ┌──────────────────────────────┐
│  Strategy Resolver          │  │   Eloquent Models            │
│ `PaymentGatewayManager`     │  │ `app/Models/`                │
│ injects map of strategies   │  │ Order · Payment · User       │
│ from `PaymentServiceProv-`  │  │ casts → enums, relations     │
│ `ider`                      │  │                              │
└────────────┬────────────────┘  └────────────┬─────────────────┘
             ▼                                ▼
┌────────────────────────────┐  ┌──────────────────────────────┐
│  Strategies (Gateways)      │  │  MySQL 8.0 (`tl13-mysql`)    │
│ `app/Services/Payment/      │  │  Redis 7 (`tl13-redis`)      │
│  Gateways/`                 │  │  cache · session · queue     │
│  Momo · Paypal · ZaloPay    │  │                              │
│  · BankTransfer             │  │                              │
│ implement `PaymentGateway`  │  │                              │
└────────────┬────────────────┘  └──────────────────────────────┘
             ▼
┌────────────────────────────┐
│ External Payment Providers  │
│   (currently mocked)        │
└────────────────────────────┘

Cross-cut: `app/Exceptions/HttpDomainException` rendered in
`bootstrap/app.php` → JSON `{message, code, context}` + status code.
```

## Component Responsibilities

| Component | Responsibility | File |
|-----------|----------------|------|
| Bootstrap / Exception renderer | Wire routing, force JSON for `api/*`, render domain exceptions | `bootstrap/app.php` |
| Provider registry | Load `AppServiceProvider`, `PaymentServiceProvider` | `bootstrap/providers.php` |
| Payment binding | Build `PaymentGatewayManager` singleton with 4 gateways | `app/Providers/PaymentServiceProvider.php` |
| API Routes | Sanctum-protected `auth/*`, `orders/*`, `payments/*`; public `payments/webhooks/{method}` | `routes/api.php` |
| Auth Controller | `login` → `LoginAction`, `logout` → `LogoutAction` | `app/Http/Controllers/Api/AuthController.php` |
| Order Controller | `store` / `show`, authorize via `Gate` | `app/Http/Controllers/Api/OrderController.php` |
| Payment Controller | `initiate` / `show` / `webhook`; resolve `PaymentMethod` enum | `app/Http/Controllers/Api/PaymentController.php` |
| FormRequests | Validate + expose typed accessors / `toData()` DTO builder | `app/Http/Requests/*Request.php` |
| Policies | Per-resource authorization predicates | `app/Policies/{OrderPolicy,PaymentPolicy}.php` |
| API Resources | Shape outbound JSON | `app/Http/Resources/*Resource.php` |
| Use-case Actions | Single-method `handle()` orchestration entry | `app/Actions/{Auth,Order,Payment}/*Action.php` |
| Domain DTOs | Immutable `readonly` data carriers | `app/DTO/{Auth,Order,Payment}/*Data.php` |
| Domain Enums | Closed sets + behavior (`isFinal()`, `label()`) | `app/Enums/{OrderStatus,PaymentMethod,PaymentStatus}.php` |
| Domain Exceptions | `HttpDomainException` base + per-domain subclasses | `app/Exceptions/HttpDomainException.php`, `app/Exceptions/{Auth,Payment}/*.php` |
| Payment Service | Orchestrator: gateway call + persistence + state transitions | `app/Services/Payment/PaymentService.php` |
| Gateway Manager | Strategy resolver keyed by `PaymentMethod->value` | `app/Services/Payment/PaymentGatewayManager.php` |
| Gateway Strategies | One implementation per provider, fulfil `PaymentGateway` contract | `app/Services/Payment/Gateways/*Gateway.php` |
| Gateway Contract | Interface for `initiate`, `verify`, `parseWebhook` | `app/Contracts/Payment/PaymentGateway.php` |
| Models | Eloquent persistence + relations + enum casts (no business logic) | `app/Models/{User,Order,Payment}.php` |

## Pattern Overview

**Overall:** MVC + Layered Architecture with DDD-lite flavors. Code is organized **by type** (`Actions/`, `DTO/`, `Enums/`, `Services/`, ...), NOT by module/bounded context. Within the Payment slice, a **Strategy pattern** plus a **Manager (resolver)** abstract the four gateway implementations behind `App\Contracts\Payment\PaymentGateway`.

**Key Characteristics:**
- Controllers are thin: `validate (FormRequest) → authorize (Gate/Policy) → call Action → return Resource`. Concrete examples: `app/Http/Controllers/Api/PaymentController.php`, `app/Http/Controllers/Api/OrderController.php`.
- Application layer = `App\Actions\*` — each Action is `final readonly`, has exactly one public method `handle()`, and contains no I/O outside delegation. Examples: `app/Actions/Payment/InitiatePaymentAction.php`, `app/Actions/Order/CreateOrderAction.php`.
- Domain orchestration lives in `App\Services\Payment\PaymentService` (transactions + state transitions). Models stay pure (relations / casts only).
- Cross-boundary data carried by immutable DTOs in `app/DTO/`; closed sets as PHP 8.3 string-backed enums in `app/Enums/`.
- All domain failures extend `HttpDomainException` and are rendered to a uniform JSON envelope by the closure registered in `bootstrap/app.php`.
- Dependency Inversion: Actions/Services depend on **contracts** (`Illuminate\Contracts\Hashing\Hasher`, `Illuminate\Contracts\Auth\Access\Gate`, `Illuminate\Database\ConnectionInterface`, `App\Contracts\Payment\PaymentGateway`) rather than facades. Bindings declared in `app/Providers/PaymentServiceProvider.php`.

## Layers

**HTTP layer:**
- Purpose: HTTP entry, validation, authorization, response shaping
- Location: `app/Http/`, `app/Policies/`
- Contains: Controllers (`app/Http/Controllers/Api/`), FormRequests (`app/Http/Requests/`), API Resources (`app/Http/Resources/`), Policies (`app/Policies/`)
- Depends on: Application layer (Actions), Domain (DTO, Enums, Models for route binding)
- Used by: Routes (`routes/api.php`)

**Application layer (use-cases):**
- Purpose: One use-case per class; orchestrate domain to satisfy a controller call
- Location: `app/Actions/{Auth,Order,Payment}/`
- Contains: `LoginAction`, `LogoutAction`, `CreateOrderAction`, `InitiatePaymentAction`, `VerifyPaymentAction`, `HandlePaymentWebhookAction`
- Depends on: Domain Services (`PaymentService`), Models, Hasher contract
- Used by: Controllers (resolved via method-injection)

**Domain layer:**
- Purpose: Business invariants, vocabulary, contracts
- Location: `app/Enums/`, `app/DTO/{Auth,Order,Payment}/`, `app/Contracts/Payment/`, `app/Exceptions/`
- Contains: Enums (`OrderStatus`, `PaymentMethod`, `PaymentStatus`), DTOs (`LoginData`, `TokenIssuedData`, `CreateOrderData`, `InitiatePaymentData`, `PaymentInitiationResult`, `PaymentVerificationResult`, `WebhookResult`), interface (`PaymentGateway`), exceptions (`HttpDomainException` + subclasses)
- Depends on: Nothing in upper layers; only PHP / framework primitives
- Used by: Application + Infrastructure layers

**Infrastructure layer:**
- Purpose: Persistence + external integrations
- Location: `app/Models/`, `app/Services/Payment/{Gateways/, PaymentGatewayManager, PaymentService}`, `app/Providers/`
- Contains: Eloquent Models, gateway strategies, `PaymentGatewayManager`, `PaymentService`
- Depends on: Domain (contracts, enums, DTOs, exceptions), Eloquent, config repository
- Used by: Application layer

**Boundary / Bootstrap:**
- Purpose: Wire HTTP, console, providers, exception rendering
- Location: `bootstrap/app.php`, `bootstrap/providers.php`, `routes/`, `config/`
- Used by: Laravel runtime

## Data Flow

### Primary Request Path — `POST /api/payments/initiate`

1. nginx (`docker/nginx/*`) routes to PHP-FPM (`app:9000`).
2. `bootstrap/app.php:11` configures routing + JSON exception renderer.
3. `routes/api.php:22` matches behind `auth:sanctum` → `PaymentController::initiate`.
4. Laravel resolves `InitiatePaymentRequest` (`app/Http/Requests/InitiatePaymentRequest.php:24`) — validates `order_id`, `method`.
5. `PaymentController::initiate` (`app/Http/Controllers/Api/PaymentController.php:28`) calls `$request->order()`, then `$this->gate->authorize('pay', $order)` → `OrderPolicy::pay` (`app/Policies/OrderPolicy.php:17`).
6. Action `InitiatePaymentAction::handle` (`app/Actions/Payment/InitiatePaymentAction.php:16`) delegates to `PaymentService::initiate`.
7. `PaymentService::initiate` (`app/Services/Payment/PaymentService.php:23`) builds `InitiatePaymentData`, asks `PaymentGatewayManager::for($method)` (`app/Services/Payment/PaymentGatewayManager.php:18`), invokes `PaymentGateway::initiate`, then `DatabaseConnection::transaction` to persist a `Payment` row.
8. Returned `Payment` model wrapped in `PaymentResource` (`app/Http/Resources/PaymentResource.php:14`) → JSON response.

### Webhook Flow — `POST /api/payments/webhooks/{method}` (public, no Sanctum)

1. `routes/api.php:26` → `PaymentController::webhook` (`app/Http/Controllers/Api/PaymentController.php:52`).
2. `PaymentMethod::tryFrom($method)` else `PaymentMethodNotSupportedException`.
3. Headers normalized to lowercase; raw payload taken from `$request->all()`.
4. `HandlePaymentWebhookAction::handle` (`app/Actions/Payment/HandlePaymentWebhookAction.php:19`) → `PaymentService::handleWebhook`.
5. Gateway parses + verifies signature (`Gateways/*Gateway::parseWebhook`), throws `GatewayException` on failure.
6. `Payment` resolved by `(method, gateway_reference)`; status applied via `applyStatus()` inside `DB::transaction`; on `Succeeded`, owning `Order` flipped to `OrderStatus::Paid`.

### Auth Flow — `POST /api/auth/login`

1. `routes/api.php:12` → `AuthController::login` (`app/Http/Controllers/Api/AuthController.php:18`).
2. `LoginRequest::toData()` → `LoginData` DTO.
3. `LoginAction::handle` (`app/Actions/Auth/LoginAction.php:17`) checks user via `User::query()` + injected `Hasher`; on miss throws `InvalidCredentialsException` (`app/Exceptions/Auth/InvalidCredentialsException.php`).
4. `User::createToken('api')` → `TokenIssuedData` → `TokenResource`.

### Error Path — Domain exception

1. Any `HttpDomainException` thrown anywhere downstream.
2. Closure in `bootstrap/app.php:27` matches via `$exceptions->render(...)`.
3. `HttpDomainException::render` (`app/Exceptions/HttpDomainException.php:33`) returns `{ message, code, context }` JSON with subclass-defined `$httpStatus`.
4. JSON-only enforcement at `bootstrap/app.php:25` guarantees this for any `api/*` request even without `Accept: application/json`.

**State Management:**
- Persistent state: MySQL via Eloquent (`Order`, `Payment`, `User`, `personal_access_tokens`).
- Session / cache / queue: Redis (configured; queue not currently used in code paths).
- Auth state: Sanctum personal access tokens (`HasApiTokens` on `User`).
- No in-process global mutable state; `PaymentGatewayManager` is `final readonly` holding a frozen map.

## Key Abstractions

**`PaymentGateway` contract:**
- Purpose: Provider-agnostic surface for the Payment slice (`initiate`, `verify`, `parseWebhook`)
- Examples: `app/Contracts/Payment/PaymentGateway.php`, implementations in `app/Services/Payment/Gateways/{MomoGateway,PaypalGateway,ZaloPayGateway,BankTransferGateway}.php`
- Pattern: Strategy. Resolver = `PaymentGatewayManager`. Bound in `app/Providers/PaymentServiceProvider.php`.

**`PaymentMethod` enum (Strategy key):**
- Purpose: Closed set of supported gateways; doubles as the routing key into `PaymentGatewayManager`
- Examples: `app/Enums/PaymentMethod.php`
- Pattern: PHP 8.3 string-backed enum + behavior method (`label()`).

**Action (use-case unit):**
- Purpose: Single-method orchestrator invoked by a controller
- Examples: `app/Actions/Payment/InitiatePaymentAction.php`, `app/Actions/Auth/LoginAction.php`, `app/Actions/Order/CreateOrderAction.php`
- Pattern: Command / Single-Method-Object. Always `final readonly`, ctor DI, exactly `public function handle(...)`.

**DTO (`*Data` / `*Result`):**
- Purpose: Immutable typed transport between layers (replaces loose arrays)
- Examples: `app/DTO/Payment/InitiatePaymentData.php`, `app/DTO/Payment/PaymentInitiationResult.php`, `app/DTO/Auth/TokenIssuedData.php`
- Pattern: PHP 8.3 `final readonly class` with constructor property promotion.

**`HttpDomainException`:**
- Purpose: Polymorphic base for domain failures with HTTP status + structured context
- Examples: `app/Exceptions/HttpDomainException.php`, subclasses in `app/Exceptions/Auth/`, `app/Exceptions/Payment/`
- Pattern: Template method (`render()`) + per-subclass `$httpStatus`.

## Entry Points

**HTTP API:**
- Location: `public/index.php` → `bootstrap/app.php` → `routes/api.php`
- Triggers: nginx (container `tl13-nginx`) FastCGI to PHP-FPM `app:9000`
- Responsibilities: Routing under `/api/*` and `/up` health, Sanctum auth groups, public webhook endpoint

**Web (single page):**
- Location: `routes/web.php` returns `welcome` view
- Triggers: Browser hitting `/`
- Responsibilities: Smoke-test page only; no business surface

**Artisan CLI:**
- Location: `artisan` → `bootstrap/app.php` (`commands: routes/console.php`)
- Triggers: `docker exec tl13-app php artisan ...`
- Responsibilities: Migrations, seeders, tinker. No custom commands defined yet.

**Queue worker (configured, unused):**
- Location: Redis-backed queue per `config/queue.php`; jobs table migration `database/migrations/0001_01_01_000002_create_jobs_table.php`
- Triggers: Not wired — no `Queue::push` / `dispatch()` calls in current code paths
- Responsibilities: Reserved for future async work (mail, gateway retries)

## Architectural Constraints

- **Threading:** Single-request PHP-FPM workers; no shared in-memory state across requests. `PaymentGatewayManager` singleton lives only within one request lifecycle.
- **Global state:** None. `PaymentGatewayManager` is `final readonly` with a frozen array; service providers register no mutable singletons.
- **Circular imports:** None observed. Layering Domain ← Application ← HTTP is respected; `app/Models/` references `app/Enums/` only.
- **PHP version pin:** PHP `^8.3` required (`composer.json`). Container is `php:8.3-fpm-alpine` — host PHP 8.2 must NOT run composer per `CLAUDE.md`.
- **strict types:** Every file under `app/`, `database/`, `tests/`, `bootstrap/` declares `declare(strict_types=1);`.
- **Facade ban inside Service/Action/Job:** Enforced by convention (see `CLAUDE.md`). Inject contracts: `Illuminate\Contracts\Hashing\Hasher` (used in `LoginAction`), `Illuminate\Database\ConnectionInterface` (used in `PaymentService`), `Illuminate\Contracts\Auth\Access\Gate` (used in controllers), `Illuminate\Contracts\Config\Repository` (used in gateways).
- **Module boundary:** Code organized by **type** today (`Actions/`, `Services/`, ...). When the project grows, `CLAUDE.md` mandates re-organization to module/domain layout (`app/Modules/Order/{Actions,Services,Models,Http}`).

## Anti-Patterns

### Business logic on the Eloquent Model

**What happens:** A future contributor adds `public function pay()` on `app/Models/Order.php`, mutating state and writing rows.
**Why it's wrong:** Violates SRP and the `CLAUDE.md` rule "Model KHÔNG chứa nghiệp vụ, chỉ scope/cast/relation". Makes orchestration impossible to unit-test in isolation from Eloquent.
**Do this instead:** Put the use-case in `app/Actions/Order/PayOrderAction.php` and the persistence + state-transition in `App\Services\Payment\PaymentService` (mirror `applyStatus()` at `app/Services/Payment/PaymentService.php:85`).

### Calling a Facade inside a Service / Action / Job

**What happens:** `Auth::user()`, `DB::transaction(...)`, `Cache::remember(...)`, `Log::info(...)` appearing in `app/Actions/**` or `app/Services/**`.
**Why it's wrong:** Hidden dependency, untestable without the framework container, breaks DIP.
**Do this instead:** Inject the contract via constructor — e.g. `Illuminate\Database\ConnectionInterface` like `app/Services/Payment/PaymentService.php:21`, `Illuminate\Contracts\Hashing\Hasher` like `app/Actions/Auth/LoginAction.php:15`, `Illuminate\Contracts\Auth\Access\Gate` like `app/Http/Controllers/Api/PaymentController.php:23`. Facades are tolerated only inside Controllers or route closures.

### `if ($type === 'X')` chain for a closed set

**What happens:** Branching on a string `method` (`if ($method === 'Momo') ... elseif ($method === 'Paypal') ...`) inside a service.
**Why it's wrong:** Violates OCP — every new method forces editing the chain. Conflicts with the existing strategy resolver.
**Do this instead:** Add a `case` to `app/Enums/PaymentMethod.php`, an implementation in `app/Services/Payment/Gateways/`, and a binding line in `app/Providers/PaymentServiceProvider.php:20`. The dispatch site `$this->gatewayManager->for($method)` does not change.

### Returning magic arrays from controllers

**What happens:** `return ['status' => 1, 'data' => $payment->toArray()];` in a controller method.
**Why it's wrong:** Not a stable contract, no type safety, breaks the uniform JSON shape.
**Do this instead:** Wrap in an API Resource (`app/Http/Resources/PaymentResource.php`, `OrderResource.php`, `TokenResource.php`). Domain failures should throw an `HttpDomainException` subclass.

### Validation logic inside a Controller

**What happens:** `$request->validate([...])` written directly inside a controller method.
**Why it's wrong:** Couples the rules to one entry, prevents reuse, violates SRP.
**Do this instead:** Add a FormRequest in `app/Http/Requests/` (mirror `app/Http/Requests/InitiatePaymentRequest.php`) with `rules()` and a `toData()` / typed accessor.

## Error Handling

**Strategy:** Throw `App\Exceptions\HttpDomainException` subclasses with explicit `$httpStatus` and structured `context`. Single render closure in `bootstrap/app.php:27` produces the wire format. Non-domain errors fall through Laravel's default exception handling but are forced to JSON for `api/*` (`bootstrap/app.php:25`).

**Patterns:**
- One subclass per failure mode: `InvalidCredentialsException` (`app/Exceptions/Auth/InvalidCredentialsException.php`), `GatewayException`, `PaymentException`, `PaymentMethodNotSupportedException` (under `app/Exceptions/Payment/`).
- Static named constructors (`::make()`, `::from()`, `::for()`) for ergonomic throw sites.
- `withContext([...])` to attach machine-readable details rendered into the response.
- Gateway failures (`GatewayException`) carry HTTP `502 Bad Gateway`; auth failures `401`; default `422 Unprocessable Entity`.

## Cross-Cutting Concerns

**Logging:** Default Laravel logger (`config/logging.php`); no custom logging in domain code. When needed, inject `Psr\Log\LoggerInterface`, NEVER use `Log` facade in Service/Action.

**Validation:** FormRequests under `app/Http/Requests/` (`CreateOrderRequest`, `InitiatePaymentRequest`, `LoginRequest`). They expose typed accessors (`->method()`, `->order()`) or `toData()` to build a DTO — controllers never read raw input.

**Authorization:** `Illuminate\Contracts\Auth\Access\Gate` injected into controllers; resource ownership predicates in `app/Policies/{OrderPolicy,PaymentPolicy}.php`. Auto-discovered by Laravel via Model→Policy convention.

**Authentication:** Laravel Sanctum. `User` uses `HasApiTokens` (`app/Models/User.php`); routes guarded by `auth:sanctum` middleware in `routes/api.php`.

**Persistence transactions:** Wrapped via injected `Illuminate\Database\ConnectionInterface` (`app/Services/Payment/PaymentService.php:21,34,91`). Multi-row writes (e.g. `Payment` save + `Order` status flip) always inside `transaction()`.

**Configuration:** All gateway secrets/URLs read via injected `Illuminate\Contracts\Config\Repository` from `config/payment.php`, sourced from `.env`. Direct `env()` calls forbidden outside config files.

---

*Architecture analysis: 2026-05-10*
