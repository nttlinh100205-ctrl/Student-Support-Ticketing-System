# Module 5: Đánh giá và Báo cáo (Report & Evaluation Service)

Dịch vụ độc lập trong kiến trúc Microservices của Hệ thống Tiếp nhận và Xử lý Yêu cầu Hỗ trợ Sinh viên. Chạy tại cổng mặc định **`:8005`**.

---

## 1. Tính năng chính

- **Báo cáo & Thống kê (`GET /api/reports/statistics`)**:
  - Thống kê tổng số lượng yêu cầu theo từng trạng thái (`new`, `received`, `in_progress`, `resolved`, `closed`, `cancelled`).
  - Phân bổ theo phòng ban (`by_department`) và loại yêu cầu (`by_support_type`).
  - Thời gian xử lý trung bình theo giờ (`avg_processing_hours`).
  - Biểu đồ biến động yêu cầu theo ngày (`requests_over_time`).
  - Thống kê mức độ hài lòng của sinh viên (`ratings_summary`).
  - Bộ lọc đa chiều: `department_id`, `support_type_id`, `from_date`, `to_date`.

- **Xuất dữ liệu chi tiết (`GET /api/reports/export`)**:
  - Xuất danh sách yêu cầu ra file CSV định dạng UTF-8 BOM, tương thích hiển thị tiếng Việt trên Microsoft Excel.

- **Đánh giá chất lượng hỗ trợ (`/api/ratings`)**:
  - Sinh viên gửi đánh giá (1-5 sao và nhận xét) cho các yêu cầu đã hoàn tất (`resolved`/`closed`).
  - Kiểm tra quyền sở hữu yêu cầu, ngăn chặn đánh giá lặp lại hoặc đánh giá yêu cầu chưa hoàn thành.
  - Xem danh sách và báo cáo tổng hợp đánh giá theo phòng ban.

- **Giao tiếp liên dịch vụ (Service-to-Service)**:
  - Tích hợp HTTP Client kết nối tới Module 2 (`:8002` - Org Service) và Module 3 (`:8003` - Request Service).
  - Hỗ trợ chế độ Mock tự động khi chạy dev hoặc test độc lập.

---

## 2. Cài đặt và Chạy

### Yêu cầu môi trường
- PHP >= 8.2 (đã hỗ trợ PHP 8.4)
- Composer >= 2.x
- SQLite / MySQL

### Các bước khởi chạy
```bash
# 1. Cài đặt dependencies
composer install

# 2. Cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 3. Chạy migration & seeder
php artisan migrate --seed

# 4. Khởi chạy service tại cổng 8005
php artisan serve --port=8005
```

---

## 3. Danh sách API Endpoints

### 3.1. Báo cáo & Thống kê (Dành cho `staff`, `department_head`, `admin`)

#### `GET /api/reports/statistics`
- **Headers**:
  ```http
  X-User-Id: 1
  X-User-Role: admin
  ```
- **Query Params (Optional)**:
  - `department_id` (int): Lọc theo phòng ban
  - `support_type_id` (int): Lọc theo loại yêu cầu
  - `from_date` (YYYY-MM-DD): Từ ngày
  - `to_date` (YYYY-MM-DD): Đến ngày
- **Response mẫu**:
  ```json
  {
    "success": true,
    "data": {
      "total_requests": 10,
      "by_status": {
        "new": 1,
        "received": 1,
        "in_progress": 2,
        "resolved": 5,
        "closed": 0,
        "cancelled": 1
      },
      "by_department": [
        { "department_id": 3, "department_name": "Phòng Công tác Sinh viên", "total": 4 }
      ],
      "by_support_type": [
        { "support_type_id": 2, "name": "Xin xác nhận sinh viên", "total": 3 }
      ],
      "avg_processing_hours": 21.8,
      "requests_over_time": [
        { "date": "2026-09-01", "total": 2 },
        { "date": "2026-09-02", "total": 2 }
      ],
      "ratings_summary": {
        "total_ratings": 5,
        "average_rating": 4.2,
        "by_stars": { "1": 0, "2": 0, "3": 1, "4": 2, "5": 2 },
        "by_department": [
          { "department_id": 3, "total_ratings": 2, "average_rating": 4.5 }
        ]
      }
    },
    "message": null
  }
  ```

#### `GET /api/reports/export`
- **Query Params**: tương tự `statistics`.
- **Response**: File CSV đính kèm (`bao-cao-yeu-cau-YYYY-MM-DD_His.csv`).

---

### 3.2. Đánh giá chất lượng hỗ trợ

#### `POST /api/ratings` (Dành cho `student`)
- **Headers**:
  ```http
  X-User-Id: 12
  X-User-Role: student
  Content-Type: application/json
  ```
- **Body**:
  ```json
  {
    "request_id": 101,
    "rating": 5,
    "comment": "Cán bộ hỗ trợ rất nhiệt tình và giải quyết nhanh."
  }
  ```

#### `GET /api/ratings` (Dành cho `staff`, `department_head`, `admin`)
- **Query Params**: `department_id`, `support_type_id`, `rating`, `from_date`, `to_date`, `per_page`.

---

## 4. Kiểm thử & Định dạng Code

```bash
# Chạy toàn bộ test
php artisan test

# Format code tự động theo PSR-12 (Laravel Pint)
./vendor/bin/pint
```
