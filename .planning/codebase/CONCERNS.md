# Concerns

**Analysis Date:** 2026-05-10

> Phạm vi dự án là **sandbox local Laravel 13 + Boost**, không phải production. Một số mục dưới đây cố ý chấp nhận như "good enough" cho sandbox; chúng được liệt kê để phân biệt rõ "đã tốt cho sandbox" vs. "blocker nếu lên production".

## Tech Debt

**HttpDomainException không override `report()`:**
- Issue: `HttpDomainException` không có `public function report(): bool` — mọi subclass (kể cả 4xx như `InvalidCredentialsException` 401, `PaymentException` 422, `PaymentMethodNotSupportedException` 400) đều rơi vào pipeline reporting mặc định. Nếu cài Sentry/Bugsnag, mỗi lần user gõ sai mật khẩu hoặc gửi method không hỗ trợ là một event noise.
- Files: `app/Exceptions/HttpDomainException.php`
- Impact: Khi tích hợp Sentry, log/error tracker bị ngập 4xx noise → quota cháy, alert fatigue, che mất 5xx thật.
- Fix approach: Thêm vào `HttpDomainException`:
  ```php
  public function report(): bool
  {
      // 4xx = lỗi client, không cần report. 5xx = server fault, để Laravel report.
      return $this->httpStatus >= 500;
  }
  ```
  Không phá test hiện có, không cần đăng ký gì thêm trong `bootstrap/app.php`.

**Mock-only payment gateways:**
- Issue: Cả 4 gateway đều là mock. `initiate()` trả URL `mock.*.local`, `verify()` đọc `payment.mock_default_status` từ config thay vì gọi HTTP, `parseWebhook()` so sánh chữ ký bằng `hash_equals(secret, X-Mock-Signature)` — không phải HMAC payload.
- Files:
  - `app/Services/Payment/Gateways/MomoGateway.php`
  - `app/Services/Payment/Gateways/PaypalGateway.php`
  - `app/Services/Payment/Gateways/ZaloPayGateway.php`
  - `app/Services/Payment/Gateways/BankTransferGateway.php`
  - `config/payment.php` (`mock_default_status`)
- Impact: Không thể chạy thật một xu nào. Mọi flow "thanh toán thành công" hiện tại là giả lập deterministic.
- Fix approach: Interface `App\Contracts\Payment\PaymentGateway` đã đủ shape production. Khi swap:
  - **Momo:** HMAC SHA256 bằng `secret_key`; webhook verify HMAC body.
  - **ZaloPay:** MAC bằng `key1` (initiate) / `key2` (callback) theo spec.
  - **PayPal:** OAuth2 client_credentials → Orders API v2; webhook verify qua `/v1/notifications/verify-webhook-signature`.
  - **BankTransfer:** Vẫn manual confirm; webhook secret nên là HMAC body, không phải static token.
  Inject `Illuminate\Http\Client\Factory` qua constructor.

**Workaround `unset($middleware)` trong bootstrap:**
- Issue: `bootstrap/app.php` truyền closure rỗng vào `withMiddleware()` chỉ để Laravel khởi tạo default web/api middleware groups, sau đó `unset($middleware)` để khỏi bị PHPStan/IDE cảnh báo "unused parameter".
- Files: `bootstrap/app.php` line 18-22
- Impact: Không impact runtime, chỉ là noise. Comment đã giải thích lý do.
- Fix approach: Có thể đổi sang `withMiddleware(static fn () => null)`. Để nguyên cũng được — known idiom Laravel 11+.

**`AppServiceProvider` rỗng:**
- Issue: `app/Providers/AppServiceProvider.php` cả `register()` và `boot()` đều `//` placeholder. CLAUDE.md yêu cầu "Bind contract trong AppServiceProvider", nhưng `PaymentServiceProvider` tự bind riêng.
- Files: `app/Providers/AppServiceProvider.php`
- Impact: Vô hại hiện tại. Khi thêm contract mới, dev có thể phân vân nên bind ở đâu.
- Fix approach: Quy ước rõ trong CLAUDE.md: domain bindings sống ở service provider riêng theo module (`PaymentServiceProvider`...). `AppServiceProvider` chỉ giữ cross-cutting (rate limiter, gate::before, model::shouldBeStrict).

## Known Bugs

Hiện không phát hiện bug functional. Test suite hiện có cấu trúc đầy đủ.

## Security Considerations

**Không có rate limiting trên `/api/auth/login`:**
- Risk: Credential stuffing / brute-force. Endpoint chấp nhận unlimited requests.
- Files: `routes/api.php` line 12 (`Route::post('login', ...)` không có middleware throttle), `app/Actions/Auth/LoginAction.php`
- Current mitigation: Không có. Default `throttle:api` cũng chưa apply lên `auth/login`.
- Recommendations:
  - Đăng ký named limiter trong `AppServiceProvider::boot()`:
    ```php
    RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by($r->input('email').$r->ip()));
    ```
  - Apply: `Route::post('login', ...)->middleware('throttle:login')`.

**Không có idempotency key trên `/api/payments/initiate`:**
- Risk: Client retry → tạo 2 hàng `payments` cho cùng 1 `Order`. `gateway_reference` được generate mới mỗi lần (`Str::ulid()`), unique constraint không chặn.
- Files: `app/Http/Controllers/Api/PaymentController.php::initiate`, `app/Services/Payment/PaymentService.php::initiate` (line 23-47), `app/Http/Requests/InitiatePaymentRequest.php`
- Current mitigation: Không có. `Order` cũng không bị constraint "1 payment processing tại 1 thời điểm".
- Recommendations:
  - Yêu cầu header `Idempotency-Key` (UUID client-generated). Cache map `key → payment_id` trong Redis với TTL 24h.
  - Hoặc: trong `PaymentService::initiate()`, check `Payment::where('order_id', ...)->whereIn('status', [Processing, Succeeded])->exists()` trước khi tạo mới.

**Webhook không có replay protection / không HMAC payload:**
- Risk:
  1. Replay: cùng payload + cùng signature có thể replay vô hạn lần. `applyStatus()` chạy lại transaction; webhook sau ghi đè/giữ payload trước (xem mục Performance bên dưới).
  2. Signature shape sai: hiện so `hash_equals($expected_secret, $received_signature)` — chuỗi static, không phải HMAC. Lộ secret là pwn.
- Files:
  - `app/Services/Payment/Gateways/MomoGateway.php::parseWebhook` (line 50-71)
  - `app/Services/Payment/Gateways/PaypalGateway.php::parseWebhook`
  - `app/Services/Payment/Gateways/ZaloPayGateway.php::parseWebhook`
  - `app/Services/Payment/Gateways/BankTransferGateway.php::parseWebhook`
  - `app/Services/Payment/PaymentService.php::handleWebhook` (line 70-80, không check final status)
- Current mitigation: Vì là mock nên acceptable. `verify()` (line 51-53) skip khi status đã final, nhưng `handleWebhook()` thì KHÔNG.
- Recommendations:
  - Real gateway: signature = `hash_hmac('sha256', $rawBody, $secret)` so với header.
  - Replay: bảng `webhook_events` với `UNIQUE webhook_event_id` từ payload, reject nếu trùng.
  - `PaymentService::handleWebhook` nên skip `applyStatus()` nếu `$payment->status->isFinal()`.

**`gateway_payload` lưu raw payload không filter:**
- Risk: Real gateway có thể trả PII (last4, billing address). DB leak = PCI scope expanded.
- Files: `app/Services/Payment/PaymentService.php` (line 41-46, 93), `database/migrations/2026_05_03_150718_create_payments_table.php`
- Current mitigation: Mock không trả PII.
- Recommendations: Trước khi swap real gateway, định nghĩa whitelist field, scrub trong `applyStatus()`.

**Default secret hardcode trong `config/payment.php`:**
- Risk: `webhook_secret` mặc định là `'mock-momo-secret'`, `'mock-zalopay-secret'`, ... — nếu `.env` thiếu, app vẫn chạy với secret biết trước.
- Files: `config/payment.php` (line 31, 39, 47, 55)
- Current mitigation: Mock context.
- Recommendations: Production đổi default thành `''` → fail hard nếu thiếu env.

## Performance Bottlenecks

**Lazy load relation trong `PaymentService::applyStatus`:**
- Problem: `$payment->order->update([...])` (line 102) lazy-load `order` bên trong transaction. Mỗi webhook = 2 query + 1 update. Vi phạm rule "không lazy load" trong CLAUDE.md.
- Files: `app/Services/Payment/PaymentService.php` line 102
- Cause: Không eager-load.
- Improvement path: `Payment::query()->with('order')->where(...)->firstOrFail()` ở `handleWebhook()` (line 74-77). Hoặc dùng `Order::where('id', $payment->order_id)->update(...)` để skip hydration.

**Webhook ghi đè `gateway_payload` qua operator `+` (bug tiềm tàng):**
- Problem: `($payment->gateway_payload ?? []) + [$source => $rawPayload]` (line 93). Operator `+` của PHP **không** override key đã có — nếu `webhook` đã có sẵn từ event trước, payload mới bị bỏ.
- Files: `app/Services/Payment/PaymentService.php` line 93
- Cause: Dùng `+` thay vì `array_merge`.
- Improvement path: `array_merge($payment->gateway_payload ?? [], [$source => $rawPayload])`. Hoặc tốt hơn: lưu list (`['webhook_history' => [...]]`) thay vì map theo source — không mất lịch sử.

## Fragile Areas

**MySQL credentials chỉ init lần đầu:**
- Files: `docker-compose-win-local.yml` (line 49-52), `CLAUDE.md`
- Why fragile: MySQL image chỉ chạy init script khi data dir trống. Đổi `DB_USERNAME`/`DB_PASSWORD` trong `.env` xong `docker compose up` lại → user/db mới **không** được tạo.
- Safe modification: `docker compose down -v` (xoá volume `test-laravel13-1_mysql-data`) rồi up lại; hoặc `docker exec tl13-mysql mysql -uroot -p... -e "ALTER USER..."`.
- Test coverage: N/A (infra concern).

**MySQL healthcheck dùng `mysqladmin ping`:**
- Files: `docker-compose-win-local.yml` line 64
- Why fragile: `mysqladmin ping` về kỹ thuật **không cần auth** để pass — password sai vẫn báo `mysqld is alive`. Trước đó từng hardcode `rootsecret` mà healthcheck vẫn xanh.
- Safe modification: Đổi sang `["CMD", "mysql", "-uroot", "-p${DB_ROOT_PASSWORD}", "-e", "SELECT 1"]` để verify cred thật.
- Test coverage: N/A.

**`PHP host = 8.2` < framework yêu cầu `^8.3`:**
- Files: `composer.json` line 9 (`"php": "^8.3"`), `Dockerfile.local`, `CLAUDE.md`
- Why fragile: Composer chạy trên host (PHP 8.2) sẽ fail platform check. Mọi `composer install/update/require` PHẢI chạy trong container `tl13-app`.
- Safe modification: Đã tài liệu trong CLAUDE.md. **Không** dùng `--ignore-platform-reqs`.
- Test coverage: N/A.

**`InitiatePaymentRequest::order()` query tách rời validation:**
- Files: `app/Http/Requests/InitiatePaymentRequest.php` line 37-40
- Why fragile: `findOrFail()` trong helper `order()` chạy SAU validation `Rule::exists('orders', 'id')`. Race condition: order bị xoá giữa 2 query → 404 thay vì 422 nhất quán. Hai query thay vì một.
- Safe modification: Dùng route model binding `Route::post('payments/initiate/{order}', ...)`. Hoặc cache `Order` đã resolve trong attribute private trên FormRequest.
- Test coverage: Test happy path đầy đủ; không có test cho race condition.

## Scaling Limits

Không phát hiện limit cứng ở scale sandbox. Khi lên prod:
- **Payment processing concurrency:** Không có lock trên Order khi initiate → 2 request đồng thời tạo 2 Payment processing.
- **Webhook throughput:** Mỗi webhook = 1-2 transaction synchronous. Gateway thật có thể burst (retry bão khi sự cố). Cần queue: webhook → Job → process async.

## Dependencies at Risk

**Laravel Boost (`^2.4`, dev):**
- Risk: Package mới (Laravel ecosystem 2025+), API có thể đổi giữa các minor. CLAUDE.md ghi "chưa chạy `boost:install`" → chưa thật sự dùng.
- Impact: Không impact runtime hiện tại.
- Migration plan: Nếu Boost không dùng tới, gỡ khỏi `require-dev` để giảm surface.

**`pestphp/pest-plugin` trong `allow-plugins` nhưng Pest không cài:**
- Risk: Discrepancy. `composer.json` line 82 `"pestphp/pest-plugin": true`. Tests viết theo PHPUnit (xem `LoginTest.php` extend `Tests\TestCase`, `public function test_*`), `phpunit.xml` cấu hình PHPUnit thuần. Grep cho `Pest|it(|describe(` trong `tests/` → không có match.
- Impact: Không impact (plugin chỉ active khi pest core được require). Dev mới đọc `composer.json` có thể nhầm.
- Migration plan: Hoặc gỡ `pestphp/pest-plugin` khỏi `allow-plugins`, hoặc add `pestphp/pest` thật và migrate suite. Quyết định trước khi phình to. **Kết luận:** test runner thực tế là **PHPUnit ^11.5.50**.

## Missing Critical Features

**Không có refund flow:**
- Problem: `App\Contracts\Payment\PaymentGateway` chỉ có `initiate / verify / parseWebhook` — không có `refund(string $reference, string $amount)`. Migration `payments` không có `refunded_at` / `refund_amount`.
- Blocks: Không thể hoàn tiền user qua API.
- Status: "Out of scope" trong plan ban đầu. Future phase.

**Không có audit log cho thay đổi `Payment.status`:**
- Problem: Status đổi qua `applyStatus()` chỉ ghi vào cột JSON `gateway_payload[$source]`. Không có bảng `payment_status_history` để truy vết.
- Blocks: Khi gateway và DB lệch nhau, không có ground truth.
- Status: Future work.

**Không có observability / structured logging:**
- Problem: Không có Sentry/Bugsnag, không có structured log JSON, không có Telescope trong prod-like config.
- Blocks: Debug production khó. Liên quan tới mục #1 (`HttpDomainException::report()`).
- Status: Khi cài Sentry, làm cùng lúc fix #1.

**Không có queue cho side effects:**
- Problem: `applyStatus()` cập nhật `Order.status = Paid` synchronous trong cùng request webhook (line 101-103). Mở rộng (gửi email, notify Slack, analytics) sẽ chậm và gateway có thể timeout.
- Blocks: CLAUDE.md đã yêu cầu "việc >200ms qua Queue Job" — hiện chưa vi phạm.
- Status: Cần lúc thêm side effect. Stack đã sẵn (Redis + queue connection).

## Test Coverage Gaps

**Không test `report()` skip Sentry:**
- What's not tested: Logic `HttpDomainException::report()` (chưa tồn tại).
- Files: Sẽ là `tests/Unit/Exceptions/HttpDomainExceptionTest.php`
- Risk: Nếu thêm `report()`, không có guard regression.
- Priority: Medium (chỉ quan trọng khi tích hợp Sentry).

**Không test rate limiting auth:**
- What's not tested: Login bị throttle sau N request fail.
- Files: `tests/Feature/Auth/LoginTest.php` (chỉ có 3 test: happy / wrong-password / missing-fields)
- Risk: Khi thêm throttle, không phát hiện regression.
- Priority: High khi thêm rate limit.

**Không test idempotency payment initiate:**
- What's not tested: Gửi cùng request 2 lần → cùng `payment_id`.
- Files: `tests/Feature/Payment/InitiatePaymentTest.php`
- Risk: Hồi quy khi sửa logic.
- Priority: High khi implement idempotency.

**Không test webhook replay / final-status guard:**
- What's not tested: Cùng webhook gửi 2 lần — payment status không bị "downgrade" lại Processing nếu đã Succeeded; lịch sử webhook không bị mất.
- Files: `tests/Feature/Payment/HandleWebhookTest.php` (chỉ có 3 test: signature OK / sai / unknown method)
- Risk: Cao nếu gateway retry.
- Priority: High khi swap real gateway.

**Không test transaction rollback:**
- What's not tested: `applyStatus()` chạy `DB::transaction()` (line 91). Nếu `$payment->order->update()` throw thì payment status có rollback không?
- Files: `tests/Feature/Payment/HandleWebhookTest.php`, `tests/Feature/Payment/VerifyPaymentTest.php`
- Risk: Inconsistent state giữa `payments.status = Succeeded` và `orders.status != Paid`.
- Priority: Medium.

**Không test `gateway_payload` merge với operator `+`:**
- What's not tested: Webhook đã ghi `gateway_payload[webhook]`; webhook thứ 2 đến — payload mới bị `+` operator giữ payload cũ (xem mục Performance).
- Files: `tests/Feature/Payment/HandleWebhookTest.php`
- Risk: Audit trail mất.
- Priority: Medium.

---

*Concerns audit: 2026-05-10*
