# Testing

**Analysis Date:** 2026-05-10

## Test Framework

- **Runner:** PHPUnit `^11.5.50` (`composer.json` require-dev). NOT Pest — `tests/Pest.php` không tồn tại, không có `pest.xml`. Bỏ qua mọi guide Boost gợi ý Pest.
- **Config file:** `phpunit.xml` (root repo).
- **Mocking:** Mockery `^1.6` + PHPUnit native `createMock()`.
- **Faker:** `fakerphp/faker ^1.23` qua factory.
- **Collision:** `nunomaduro/collision ^8.6` cho output đẹp.

**Run commands:**
```bash
composer test                 # alias: clear config + artisan test
php artisan test              # qua Laravel test runner
vendor/bin/phpunit            # PHPUnit trực tiếp
php artisan test --filter=InitiatePaymentTest
```

Trong Docker container (host PHP 8.2, KHÔNG chạy host):
```bash
docker compose -f docker-compose-win-local.yml exec app php artisan test
```

## Test Environment (`phpunit.xml`)

Bootstrap `vendor/autoload.php`. Source coverage scope: `app/`. Test suite chia 2: `Unit` (`tests/Unit`) và `Feature` (`tests/Feature`).

ENV override khi test:
- `APP_ENV=testing`, `BCRYPT_ROUNDS=4` (test nhanh)
- `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` — SQLite in-memory, KHÔNG đụng tới MySQL container
- `CACHE_STORE=array`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`
- `QUEUE_CONNECTION=sync` — job chạy đồng bộ trong test
- `BROADCAST_CONNECTION=null`, `PULSE_ENABLED=false`, `TELESCOPE_ENABLED=false`

## Test File Organization

**Tách 2 trục: layer (Unit/Feature) × domain (Auth/Order/Payment).**

```
tests/
├── TestCase.php                                     # Base, extends Laravel BaseTestCase
├── Feature/
│   ├── ExampleTest.php                              # Skeleton mặc định
│   ├── Auth/LoginTest.php
│   ├── Order/CreateOrderTest.php
│   └── Payment/
│       ├── InitiatePaymentTest.php
│       ├── VerifyPaymentTest.php
│       └── HandleWebhookTest.php
└── Unit/
    ├── ExampleTest.php                              # Skeleton mặc định
    ├── Auth/
    │   ├── LoginActionTest.php
    │   └── LogoutActionTest.php
    ├── Order/
    │   └── CreateOrderActionTest.php
    └── Payment/
        ├── PaymentGatewayManagerTest.php
        └── Gateways/
            ├── MomoGatewayTest.php
            ├── PaypalGatewayTest.php
            ├── ZaloPayGatewayTest.php
            └── BankTransferGatewayTest.php
```

**Naming:**
- File: `<ClassUnderTest>Test.php` cho Unit (e.g. `LoginActionTest` test `LoginAction`), `<Behaviour>Test.php` cho Feature (e.g. `InitiatePaymentTest` test endpoint POST /api/payments/initiate).
- Class: `final class XxxTest extends TestCase` (luôn `final`).
- Method: `test_<snake_case_behaviour>(): void` — return type bắt buộc.

**Test count hiện tại:** 34 tests pass, 90 assertions.

## Base Class — 2 loại

**Unit gateway test** (KHÔNG cần Laravel container, KHÔNG cần DB) — extend `PHPUnit\Framework\TestCase`:
```php
use PHPUnit\Framework\TestCase;

final class MomoGatewayTest extends TestCase
{
    private function makeGateway(string $defaultStatus = 'Succeeded'): MomoGateway
    {
        return new MomoGateway(new ConfigRepository([...]));
    }
    // ...
}
```
Pattern này dùng cho test gateway thuần — instantiate trực tiếp với `Illuminate\Config\Repository` mock data. KHÔNG kích hoạt Laravel app.

**Action / Feature test** (cần DB + container) — extend `Tests\TestCase`:
```php
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class LoginActionTest extends TestCase
{
    use RefreshDatabase;
    // ...
}
```
`tests/TestCase.php` extend `Illuminate\Foundation\Testing\TestCase as BaseTestCase`.

> **Nit:** `tests/TestCase.php` hiện thiếu `declare(strict_types=1);` — fix khi đụng tới.

## Database Refresh

**Trait:** `Illuminate\Foundation\Testing\RefreshDatabase` — dùng trong mọi test cần persistence. SQLite in-memory rebuild giữa test, transaction rollback giữa example.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

final class CreateOrderActionTest extends TestCase
{
    use RefreshDatabase;
    // ...
}
```

## Factories

Factory PSR-4 `Database\Factories\*` (xem `composer.json`). Files:
- `database/factories/UserFactory.php`
- `database/factories/OrderFactory.php`
- `database/factories/PaymentFactory.php`

**Pattern:**
- Class extends `Illuminate\Database\Eloquent\Factories\Factory`.
- `protected $model = Xxx::class;`.
- `definition(): array` trả default state.
- **State method** chained, return `static`, dùng `$this->state(fn (): array => [...])`:
  ```php
  // OrderFactory
  public function paid(): static {
      return $this->state(fn (): array => ['status' => OrderStatus::Paid]);
  }

  // PaymentFactory
  public function method(PaymentMethod $method): static {
      return $this->state(fn (): array => ['method' => $method]);
  }
  public function succeeded(): static {
      return $this->state(fn (): array => [
          'status' => PaymentStatus::Succeeded,
          'paid_at' => now(),
      ]);
  }
  ```
- Faker: `$this->faker->randomElement(PaymentMethod::cases())`, `$this->faker->randomFloat(2, 10000, 5000000)`.
- Foreign key qua factory inline: `'order_id' => Order::factory()` hoặc `'user_id' => User::factory()`.

**Sử dụng:**
```php
$user = User::factory()->create();
$order = Order::factory()->for($user)->create();
$payment = Payment::factory()
    ->for($order)
    ->method(PaymentMethod::MomoMethod)
    ->create(['status' => PaymentStatus::Processing]);

$paidOrder = Order::factory()->paid()->create();
```

## Authentication trong Test

**Sanctum** — `Laravel\Sanctum\Sanctum::actingAs($user)` cho mọi feature test cần auth user.

```php
use Laravel\Sanctum\Sanctum;

Sanctum::actingAs(User::factory()->create());
$this->postJson('/api/orders', [...])->assertCreated();
```

Negative auth test:
```php
$this->postJson('/api/orders', [...])->assertUnauthorized();   // chưa login
$this->postJson('/api/payments/initiate', [...])->assertForbidden();  // login nhưng sai owner
```

## DataProvider (PHPUnit 11 attribute)

Dùng attribute `#[DataProvider]` (KHÔNG dùng docblock `@dataProvider` cũ).

```php
use PHPUnit\Framework\Attributes\DataProvider;

final class InitiatePaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{method: PaymentMethod, expectsRedirect: bool, expectsQr: bool}>
     */
    public static function methodProvider(): array
    {
        return [
            'momo' => ['method' => PaymentMethod::MomoMethod, 'expectsRedirect' => true, 'expectsQr' => false],
            'paypal' => ['method' => PaymentMethod::PaypalMethod, 'expectsRedirect' => true, 'expectsQr' => false],
            'zalopay' => ['method' => PaymentMethod::ZaloPayMethod, 'expectsRedirect' => true, 'expectsQr' => false],
            'bank_transfer' => ['method' => PaymentMethod::BankTransferMethod, 'expectsRedirect' => false, 'expectsQr' => true],
        ];
    }

    #[DataProvider('methodProvider')]
    public function test_user_can_initiate_payment(PaymentMethod $method, bool $expectsRedirect, bool $expectsQr): void
    {
        // ...
    }
}
```

Provider rule:
- `public static function`, return `array<string, array{...}>` (named keys cho test name dễ đọc).
- PHPDoc `@return array<string, array{...}>` để static analysis hiểu shape.

## Mocking

**Provider mock dùng `createMock()`** cho test pure unit (không Laravel):
```php
public function test_it_returns_registered_gateway_for_known_method(): void
{
    $gateway = $this->createMock(PaymentGateway::class);
    $manager = new PaymentGatewayManager([
        PaymentMethod::MomoMethod->value => $gateway,
    ]);

    $this->assertSame($gateway, $manager->for(PaymentMethod::MomoMethod));
}
```
Xem `tests/Unit/Payment/PaymentGatewayManagerTest.php`.

**Config injection thay mock** cho gateway test — instantiate `Illuminate\Config\Repository` với data fixed thay vì mock toàn bộ:
```php
private function makeGateway(string $defaultStatus = 'Succeeded', string $secret = 'secret'): MomoGateway
{
    return new MomoGateway(new ConfigRepository([
        'payment' => [
            'mock_default_status' => $defaultStatus,
            'gateways' => ['Momo' => ['webhook_secret' => $secret]],
        ],
    ]));
}
```
Xem `tests/Unit/Payment/Gateways/MomoGatewayTest.php`.

**Real hasher trong action test** thay mock — `BcryptHasher` instantiate trực tiếp (rounds = 4 do `phpunit.xml` set), test cả `LoginAction` + hash logic end-to-end:
```php
(new LoginAction(new BcryptHasher))->handle(new LoginData($user->email, 'right-pass'));
```
Xem `tests/Unit/Auth/LoginActionTest.php`.

**`config()->set(...)` trong feature test** để override config tại runtime:
```php
config()->set('payment.gateways.Momo.webhook_secret', 'right-secret');
config()->set('payment.mock_default_status', PaymentStatus::Succeeded->value);
```
Xem `tests/Feature/Payment/HandleWebhookTest.php`, `VerifyPaymentTest.php`.

**Mockery `^1.6`** sẵn sàng — chưa thấy dùng trong repo hiện tại.

**Không mock:** Eloquent model, DB, Hash thật (rounds=4), enum cast — test chạy SQLite in-memory đủ nhanh.

## Common Patterns

**HTTP test cho API JSON:**
```php
$this->postJson('/api/payments/initiate', [
    'order_id' => $order->id,
    'method' => $method->value,
])->assertCreated();
```

**Assertion JSON path:**
```php
$response->assertJsonPath('data.method', $method->value);
$response->assertJsonPath('data.status', PaymentStatus::Processing->value);
$response->assertJsonPath('data.paid_at', fn (?string $v): bool => $v !== null);
$response->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email', 'name']]]);
```

**Database assertion:**
```php
$this->assertDatabaseHas('payments', [
    'order_id' => $order->id,
    'method' => $method->value,
    'status' => PaymentStatus::Processing->value,
]);
$this->assertDatabaseCount('orders', 1);
```

**Status assertion:**
```php
$response->assertOk();             // 200
$response->assertCreated();        // 201
$response->assertUnauthorized();   // 401
$response->assertForbidden();      // 403
$response->assertStatus(422);      // validation
$response->assertStatus(502);      // GatewayException default httpStatus
```

**Exception testing:**
```php
$this->expectException(GatewayException::class);
$this->makeGateway(secret: 'right-secret')->parseWebhook(
    payload: ['gateway_reference' => 'MOCK-MOMO-X', 'status' => 'Succeeded'],
    headers: ['x-mock-signature' => 'wrong-secret'],
);
```

**Refresh model state sau side effect:**
```php
$this->assertSame(OrderStatus::Paid, $order->fresh()?->status);
```

## Test Types Used

- **Unit (gateway)** — extend `PHPUnit\Framework\TestCase`, KHÔNG cần Laravel app, KHÔNG cần DB. Test logic thuần.
- **Unit (action)** — extend `Tests\TestCase` + `RefreshDatabase`. Test Action với DB thật in-memory, hasher thật, không HTTP.
- **Feature** — extend `Tests\TestCase` + `RefreshDatabase`. Test full HTTP request/response stack, auth qua Sanctum.
- **E2E (browser):** không có. Chưa cài Dusk / Pest browser.

## Coverage

- **Source scope:** `app/` (`phpunit.xml:15-19`).
- **Coverage requirement:** không enforce. Không có Xdebug / pcov config trong repo. Chạy coverage:
  ```bash
  vendor/bin/phpunit --coverage-text   # cần Xdebug hoặc pcov
  ```

## Skeleton Tests Cần Cleanup

- `tests/Unit/ExampleTest.php` — `assertTrue(true)` rỗng, thiếu `declare(strict_types=1)`, không `final`.
- `tests/Feature/ExampleTest.php` — `assertStatus(200)` cho `/`, thiếu `declare(strict_types=1)`, không `final`.
- `tests/TestCase.php` — thiếu `declare(strict_types=1)`.

Khi đụng test infrastructure, fix 3 file trên cho khớp convention.

---

*Testing analysis: 2026-05-10*
