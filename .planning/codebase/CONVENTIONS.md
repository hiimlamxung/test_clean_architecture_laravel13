# Code Conventions

**Analysis Date:** 2026-05-10

> Hard rules cho dự án này được định nghĩa trong `CLAUDE.md` (root repo), section "Nguyên tắc code". Document này phản ánh trực tiếp những rule đó cộng với pattern thực tế quan sát được trong `app/`, `database/`, `tests/`.

## PHP Baseline (bắt buộc)

- **`declare(strict_types=1);` ở dòng đầu (sau `<?php`)** của MỌI file `.php` trong `app/`, `database/`, `tests/`. Verified:
  - `app/`: 46/46 file
  - `database/factories/` + `database/migrations/`: 4/4 file (`OrderFactory.php`, `PaymentFactory.php`, `2026_05_03_150713_create_orders_table.php`, `2026_05_03_150718_create_payments_table.php`)
  - `tests/`: 13/13 test thật. Skeleton mặc định `tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`, `tests/TestCase.php` còn thiếu — cần fix khi đụng tới.
- **PHP target: ^8.3** (`composer.json`). Tận dụng:
  - `final readonly class` cho DTO + Action + Service
  - `enum` (backed) thay cho magic string/int
  - Constructor property promotion: `public function __construct(private readonly Gate $gate) {}`
  - `match` expression, named arguments
- **Type hint đầy đủ:** mọi param, return, property phải có type. KHÔNG dùng `mixed` trừ khi thật sự cần (e.g. `gateway_payload`).
- **Không magic value** — string/int rời chuyển sang `enum`. Ví dụ: `app/Enums/PaymentMethod.php`, `app/Enums/PaymentStatus.php`, `app/Enums/OrderStatus.php`.
- **DTO/Value Object cho dữ liệu có cấu trúc.** Ví dụ: `app/DTO/Payment/InitiatePaymentData.php`, `app/DTO/Auth/LoginData.php`, `app/DTO/Order/CreateOrderData.php`, `app/DTO/Payment/PaymentInitiationResult.php`, `app/DTO/Payment/WebhookResult.php`.

## Naming Patterns

**Files & Classes (1 file = 1 class):**
- `XxxAction` — `app/Actions/Auth/LoginAction.php`, `app/Actions/Order/CreateOrderAction.php`, `app/Actions/Payment/InitiatePaymentAction.php`, `app/Actions/Payment/HandlePaymentWebhookAction.php`, `app/Actions/Payment/VerifyPaymentAction.php`
- `XxxService` — `app/Services/Payment/PaymentService.php`
- `XxxData` (DTO input) — `app/DTO/Payment/InitiatePaymentData.php`, `app/DTO/Auth/LoginData.php`
- `XxxResult` (DTO output) — `app/DTO/Payment/PaymentInitiationResult.php`, `app/DTO/Payment/PaymentVerificationResult.php`, `app/DTO/Payment/WebhookResult.php`, `app/DTO/Auth/TokenIssuedData.php`
- `XxxController` — `app/Http/Controllers/Api/PaymentController.php`, `AuthController.php`, `OrderController.php`
- `XxxRequest` — `app/Http/Requests/InitiatePaymentRequest.php`, `CreateOrderRequest.php`, `LoginRequest.php`
- `XxxResource` — `app/Http/Resources/PaymentResource.php`, `OrderResource.php`, `TokenResource.php`, `UserResource.php`
- `XxxPolicy` — `app/Policies/PaymentPolicy.php`, `OrderPolicy.php`
- `XxxException` — `app/Exceptions/Payment/GatewayException.php`, `PaymentException.php`, `PaymentMethodNotSupportedException.php`, `app/Exceptions/Auth/InvalidCredentialsException.php`
- `XxxGateway` (Strategy) — `app/Services/Payment/Gateways/MomoGateway.php`, `PaypalGateway.php`, `ZaloPayGateway.php`, `BankTransferGateway.php`

**Class = noun, method = verb:**
- Action method luôn là `handle()` — `LoginAction::handle()`, `InitiatePaymentAction::handle()`.
- Verbs: `initiate()`, `verify()`, `parseWebhook()`, `for()`, `view()`, `pay()`, `toData()`.

**Enum cases:** PascalCase backed-string. `PaymentMethod::MomoMethod = 'Momo'`, `PaymentStatus::Succeeded = 'Succeeded'`.

**Test methods:** `test_<behaviour_in_snake_case>(): void` — `test_login_with_valid_credentials_returns_token`, `test_webhook_marks_payment_paid_when_signature_valid`.

**Variables:** camelCase trong PHP (`$gatewayReference`, `$expectsRedirect`). snake_case cho DB column và JSON payload (`order_id`, `gateway_reference`).

## Class Structure Rules

- **`final` class mặc định** — gần như mọi class production: `final readonly class InitiatePaymentAction`, `final class PaymentController extends Controller`, `final class MomoGateway implements PaymentGateway`.
- **`readonly` cho DTO + Action + Service** khi không có mutable state.
- **1 file = 1 class.**
- **KHÔNG có public mutable property.** Property `private` (qua promotion) hoặc `protected`. Public chỉ cho DTO `public readonly`.
- **Tổ chức theo domain khi project lớn:** `app/Modules/Order/{Actions,Services,Models,Http}` (chưa áp dụng vì repo còn nhỏ).

## SOLID — Hard Rules

- **SRP** — Controller điều phối: `Form Request → Policy → Action → Resource`. Logic ở `App\Actions\*` (1 file 1 method `handle()`) hoặc `App\Services\*`. Model KHÔNG chứa nghiệp vụ — chỉ relation, cast, scope. Xem `app/Models/Payment.php`, `app/Models/Order.php`, `app/Models/User.php` (chỉ relation + `casts()`).
- **OCP** — Tránh chuỗi `if ($type === 'X')`. Dùng interface + polymorphism. Pattern thực tế: `App\Contracts\Payment\PaymentGateway` interface + 4 implementation + `PaymentGatewayManager::for(PaymentMethod)` map enum → instance.
- **LSP** — Subclass không throw thêm exception, không nới lỏng return type so với parent.
- **ISP** — Interface nhỏ, focused. `PaymentGateway` chỉ 3 method `initiate / verify / parseWebhook`.
- **DIP** — Phụ thuộc contract (`App\Contracts\*` hoặc `Illuminate\Contracts\*`), KHÔNG class concrete. Bind trong provider, inject qua constructor:
  - `app/Providers/PaymentServiceProvider.php` — bind `PaymentGatewayManager` singleton.
  - `app/Services/Payment/PaymentService.php` — inject `Illuminate\Database\ConnectionInterface as DatabaseConnection`, KHÔNG `DB::`.
  - `app/Actions/Auth/LoginAction.php` — inject `Illuminate\Contracts\Hashing\Hasher`, KHÔNG `Hash::`.
  - `app/Http/Controllers/Api/PaymentController.php` — inject `Illuminate\Contracts\Auth\Access\Gate`, KHÔNG `Gate::`.
  - `app/Services/Payment/Gateways/MomoGateway.php` — inject `Illuminate\Contracts\Config\Repository as ConfigRepository`, KHÔNG `config()` helper trong service.

## Laravel Pattern Bắt Buộc

- **Validate qua Form Request** — không validate trong Controller. Pattern: `final class XxxRequest extends FormRequest` với `authorize()`, `rules()`, method `toData(): XxxData`. Ví dụ `app/Http/Requests/CreateOrderRequest.php::toData()` build DTO từ input.
- **Authorization qua Policy/Gate** — không có `if ($user->id === $x)` trong controller. Pattern: `$this->gate->authorize('pay', $order);` (`app/Http/Controllers/Api/PaymentController.php:33`). Logic ownership trong `app/Policies/OrderPolicy.php::pay()`.
- **API response qua Resource** — không return Model thô, không magic array. Controller method return type = Resource. Resource class `final` + `@mixin <Model>` PHPDoc + `toArray(Request $request): array`.
- **Side effect qua Event + Listener.** Chưa áp dụng — khi cần thêm side effect, KHÔNG nhét vào Action/Service.
- **Việc >200ms qua Queue Job.**
- **Ghi nhiều bảng → `DB::transaction()`** qua DI contract: `$this->db->transaction(fn () => ...)` trong `PaymentService::initiate()` và `PaymentService::applyStatus()`.
- **Eager load (`->with()`) chống N+1.**
- **Custom Exception render qua `bootstrap/app.php`** với `->withExceptions(...)`. Pattern: subclass `App\Exceptions\HttpDomainException` (abstract base), set `$httpStatus`, có sẵn `render()`. API request (`api/*`) auto JSON qua `shouldRenderJsonWhen()`.

## Cấm (forbidden patterns)

| Pattern | Allowed? |
|---|---|
| Facade trong Service/Action/Job (`DB::`, `Auth::`, `Cache::`, `Log::`, `Hash::`, `Gate::`...) | ❌ Chỉ Controller hoặc closure route. Inject contract qua DI. |
| `static::method()` cho business logic | ❌ |
| `public` mutable property | ❌ (DTO `public readonly` OK) |
| Query Eloquent / DB trong Blade | ❌ |
| `dd()`, `dump()`, `var_dump()` trong commit | ❌ Verified: 0 occurrence trong `app/` |
| Magic array `['status' => 1, 'data' => ...]` thay Resource/DTO | ❌ |
| Validate trong Controller | ❌ |
| Logic nghiệp vụ trong Model | ❌ |

## Code Style (Pint)

- **Formatter:** Laravel Pint `^1.24` (require-dev). Binary: `vendor/bin/pint`.
- **Run:** `vendor/bin/pint` (hoặc `vendor/bin/pint --test` để chỉ check). Chưa có composer alias `composer pint` — gọi binary trực tiếp (hoặc qua container `app`).
- **Config:** không có `pint.json` riêng → dùng Pint default preset (Laravel preset).
- **Linting bổ sung:** chưa có PHPStan / Psalm / Rector. Type safety dựa hoàn toàn vào `declare(strict_types=1)` + native type hint + PHPDoc generic.

## Import Organization

Pint default (Laravel preset):
1. Single group `use`, alphabetical.
2. Trailing comma trong multi-line array / argument list.
3. Aliasing contract khi tên trùng concrete: `use Illuminate\Database\ConnectionInterface as DatabaseConnection;`, `use Illuminate\Contracts\Config\Repository as ConfigRepository;`.

Ví dụ chuẩn (`app/Services/Payment/PaymentService.php`):
```php
<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\DTO\Payment\InitiatePaymentData;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\ConnectionInterface as DatabaseConnection;
use Illuminate\Support\Carbon;
```

## Error Handling

**Strategy:** Custom exception subclass theo domain, render qua `HttpDomainException::render()`.

- Base abstract: `app/Exceptions/HttpDomainException.php` — có `$httpStatus` (default 422), `$context` array, `withContext()` builder, `render(): JsonResponse` trả `{message, code, context}`.
- Domain exception: subclass set `$httpStatus`. Ví dụ `app/Exceptions/Payment/GatewayException.php` (`httpStatus = 502`) có factory `GatewayException::from($gateway, $reason, $context)`. `app/Exceptions/Auth/InvalidCredentialsException.php` có factory `make()`.
- Throw pattern: named factory thay `new` khi cần context.
- Bootstrap (`bootstrap/app.php:23-28`) đăng ký một custom render duy nhất cho `HttpDomainException`.
- Action / Service throw domain exception, KHÔNG `Exception` thô. Controller KHÔNG try-catch.

## Logging

Laravel default. Cấm `Log::` facade trong Service/Action/Job — inject `Psr\Log\LoggerInterface` (chưa có example trong repo, nhưng rule giống `DB`/`Hash`/`Gate`).

## Comments

- Giải thích **why** (tiếng Việt), không **what**.
- Pattern thực tế (`bootstrap/app.php`):
  ```php
  // Để default cho web/api middleware groups được Laravel khởi tạo (xem framework MiddlewareTrait::use/web/api).
  // KHÔNG xoá closure này: Laravel chỉ apply default middleware khi withMiddleware() được gọi.
  ```
- PHPDoc bắt buộc khi return có generic không biểu diễn được bằng native: `@return array<string, mixed>`, `@return BelongsTo<Order, $this>`, `@param array<string, string> $headers`.
- `@throws GatewayException` cho method có thể throw.
- `@mixin <Model>` cho Resource: `/** @mixin Payment */ final class PaymentResource extends JsonResource`.
- Variable docblock ép kiểu auth user: `/** @var User $user */ $user = $request->user();`.

## Function / Method Design

- Constructor promotion ưu tiên.
- `Action::handle()` nhận DTO, trả Model hoặc DTO output.
- Named arguments khi gọi DTO/Result để self-document:
  ```php
  return new PaymentInitiationResult(
      gatewayReference: $reference,
      redirectUrl: "https://mock.momo.local/pay/{$reference}",
      qrData: null,
      rawPayload: [...],
  );
  ```

## Adding New Code (checklist)

1. **DTO input** trong `app/DTO/<Domain>/XxxData.php` (`final readonly`).
2. **Form Request** `app/Http/Requests/XxxRequest.php` với `authorize()`, `rules()`, `toData(): XxxData`.
3. **Action** `app/Actions/<Domain>/XxxAction.php` (`final readonly`, 1 method `handle()`).
4. **Service** `app/Services/<Domain>/XxxService.php` nếu logic phức tạp / nhiều Action share.
5. **Contract** `app/Contracts/<Domain>/XxxInterface.php` nếu cần polymorphism. Bind trong provider.
6. **Policy** `app/Policies/XxxPolicy.php` cho authorization.
7. **Resource** `app/Http/Resources/XxxResource.php` cho output.
8. **Custom Exception** subclass `HttpDomainException` trong `app/Exceptions/<Domain>/`.
9. **Controller method** chỉ điều phối: validate → authorize → action → resource.
10. **Test:** Unit cho Action/Service, Feature cho HTTP endpoint.

---

*Conventions analysis: 2026-05-10*
