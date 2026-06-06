# CLAUDE.md

Hướng dẫn dành riêng cho dự án này. Đọc kỹ trước khi đụng vào code/config.

## Tổng quan

- **Dự án:** Test Laravel 13 chạy trên Docker tại máy local Windows.
- **Mục tiêu:** Học clean architecture.

## Stack

| Thành phần | Version | Ghi chú |
|---|---|---|
| Laravel Framework | ^13.0 (đang 13.7.0) | Yêu cầu PHP ^8.3 |
| PHP (trong container) | 8.3-fpm-alpine | Host máy là 8.2 — KHÔNG chạy composer trực tiếp trên host |
| Composer | 2.x (image `composer:2`) | |
| MySQL | 8.0 | `caching_sha2_password`, utf8mb4 |
| Redis | 7-alpine | Dùng cho cache + session + queue |
| Nginx | 1.27-alpine | Web server, FastCGI sang `app:9000` |
| Laravel Boost | ^2.4 (dev) | Cho AI-assisted dev, chưa chạy `boost:install` |

## Quy tắc khi sửa code

- **Tên biến/hàm/class viết tiếng Anh.** Comment tiếng Việt nếu cần giải thích "tại sao".
- **KHÔNG** thêm tính năng / refactor ngoài phạm vi yêu cầu.
- Trước khi commit cấu hình, kiểm tra `.env` không có secret thật. File `.env` đã được `.gitignore`.

## Nguyên tắc code (luôn áp dụng — kể cả là code "thử")

Production-grade OOP + SOLID. Mọi đoạn code agent sinh ra phải tuân thủ; vi phạm = sửa lại, không merge.

### PHP nền tảng

- `declare(strict_types=1);` ở đầu **mọi** file `.php` trong `app/`, `database/`, `tests/`.
- Type hint đầy đủ: param, return, property. Tận dụng PHP 8.3: `readonly`, `enum`, union/intersection types, constructor property promotion.
- Không dùng "magic value" (string/int rời) — chuyển sang `enum`.
- DTO/Value Object cho mọi dữ liệu có cấu trúc, không truyền `array` lung tung.

### Repository pattern (bắt buộc cho mọi DB access)

Mục đích: tái sử dụng query phức tạp, tách Service/Action khỏi Eloquent cụ thể, dễ mock khi test.
