# Module 5: Đánh giá và Báo cáo (Report & Evaluation Service)

Dịch vụ độc lập trong kiến trúc Microservices của Hệ thống Tiếp nhận và Xử lý Yêu cầu Hỗ trợ Sinh viên. Chạy tại cổng mặc định **`:8005`**.

---

## 1. Tính năng chính

- **Báo cáo & Thống kê (`GET /api/reports/statistics`)**:
  - Thống kê tổng số lượng yêu cầu theo từng trạng thái (`new`, `received`, `in_progress`, `resolved`, `closed`, `cancelled`).
  - Phân bổ theo phòng ban (`by_department`) và loại yêu cầu (`by_support_type`).
  - Thời gian xử lý trung bình theo giờ (`avg_processing_hours`).
  - Biểu đồ biến động yêu cầu theo ngày (`requests_over_time`).
  - **Chỉ số SLA & Tỉ lệ quá hạn (`sla_metrics`)**:
    - Số lượng và tỷ lệ đúng hạn SLA (`sla_compliance_rate`).
    - Số lượng và tỷ lệ quá hạn SLA (`sla_overdue_rate`).
    - Danh sách các yêu cầu bị quá hạn SLA (`overdue_requests`).
  - **Bảng xếp hạng Hiệu suất & Hài lòng (`department_rankings`, `staff_rankings`)**:
    - Xếp hạng phòng ban và cán bộ dựa trên tỷ lệ giải quyết, tỷ lệ đạt SLA và điểm CSAT.
  - **Giám sát Khối lượng công việc cán bộ (`staff_workloads`)**:
    - Thống kê chi tiết số việc đang xử lý, đã xong, tổng tải và cảnh báo quá tải.
  - **Thống kê mức độ hài lòng của sinh viên (`ratings_summary`)**:
    - Điểm CSAT trung bình, phân bổ theo số sao, theo phòng ban và theo từng cán bộ.
  - **Bộ lọc đa chiều & Thời gian**: `department_id`, `support_type_id`, `staff_id`, `from_date`, `to_date`.

- **Xuất dữ liệu & Báo cáo (`GET /api/reports/export` & `GET /api/reports/export-pdf`)**:
  - **Xuất Excel (CSV)**: Xuất danh sách yêu cầu ra file CSV kèm UTF-8 BOM, hiển thị tiếng Việt chuẩn trên Excel.
  - **Xuất PDF**: Tạo file PDF báo cáo chuẩn (`DomPDF`) gồm đầy đủ tiêu đề, các chỉ số KPI, bảng xếp hạng phòng ban/cán bộ và danh sách quá hạn SLA.

- **Giao diện Dashboard trực quan (`/` hoặc `/dashboard`)**:
  - Đầy đủ 3 nhóm biểu đồ: **Biểu đồ Cột** (Khối lượng cán bộ, phân bổ sao), **Biểu đồ Tròn** (Trạng thái yêu cầu, Tỷ lệ SLA), **Biểu đồ Đường** (Xu hướng yêu cầu theo thời gian).
  - Bộ nút chọn nhanh khoảng thời gian (Hôm nay, 7 ngày qua, 30 ngày qua, Tháng 9/2026, Quý 3).


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
