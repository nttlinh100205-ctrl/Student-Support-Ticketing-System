# API Gateway (`:8000`)

Cổng vào duy nhất của frontend. Frontend chỉ gọi `http://localhost:8000/api/...`,
Gateway tra [routes.php](routes.php) rồi chuyển request xuống đúng service.

```text
Frontend ──► API Gateway :8000 ──► Auth :8001 | Org :8002 | Request :8003 | File :8004 | Report :8005
```

## Chạy

Không cần `composer install`, chỉ cần PHP có extension `curl`.

```bash
cd api-gateway
php -S localhost:8000 index.php
```

Kiểm tra: mở `http://localhost:8000/health` để xem bảng điều hướng.

Các service vẫn chạy ở cổng riêng như cũ (`php artisan serve --port=8002`...).
Service nào chưa bật thì Gateway trả `502`.

## Bảng điều hướng

| Tiền tố | Service |
|---|---|
| `/api/v1`, `/api/auth`, `/api/users` | Module 1 – Auth `:8001` |
| `/api/departments`, `/api/support-types`, `/api/staff-candidates`, `/api/faqs`, `/api/catalog` | Module 2 – Org `:8002` |
| `/api/requests`, `/api/sla` | Module 3 – Request `:8003` |
| `/api/news`, `/api/admin/news` | Module 4 – File `:8004` |
| `/api/reports`, `/api/ratings` | Module 5 – Report `:8005` |

Module thêm endpoint với tiền tố mới thì thêm một dòng vào `routes.php`.
Đổi địa chỉ service bằng biến môi trường `AUTH_SERVICE_URL`, `ORG_SERVICE_URL`,
`REQUEST_SERVICE_URL`, `FILE_SERVICE_URL`, `REPORT_SERVICE_URL`.

## Gateway làm gì

- Chuyển nguyên method, query string, body (JSON và multipart có file) và các header
  `Accept`, `Content-Type`, `Authorization`, `X-User-Id`, `X-User-Role`.
- Trả nguyên status code và body của service về frontend.
- Bật CORS để frontend ở cổng khác (`:3000`, `:8002`...) gọi được.
