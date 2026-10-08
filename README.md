# Hệ Thống Tiếp Nhận & Xử Lý Yêu Cầu Hỗ Trợ Sinh Viên
> **Student Support Ticketing System** — Kiến trúc Microservices trong mô hình Monorepo

Dự án được xây dựng theo kiến trúc **Microservices**, tổ chức và quản lý tập trung trong một **Monorepo** trên GitHub. Mỗi service hoạt động độc lập, có cơ sở dữ liệu/cổng kết nối riêng và giao tiếp thông qua giao thức HTTP RESTful API.

---

## 📚 Tài Liệu Chuẩn Hóa Dự Án (Bắt buộc đọc)

| Tài liệu | Mô tả | Ai cần đọc |
| :--- | :--- | :--- |
| 📖 **[API_CONTRACT.md](API_CONTRACT.md)** | **Bản thỏa thuận API Contract chung:** Bức tranh tổng thể, JWT payload (7 field), format response `ApiResponse`, quy ước đặt tên endpoint, mock data, status code. | Tất cả thành viên |
| 📐 **[CODING_CONVENTIONS.md](CODING_CONVENTIONS.md)** | **Quy chuẩn lập trình:** Naming convention, Controller mỏng (~15 dòng) - Service dày, Form Request, Comment/Docblock, Laravel Pint, Conventional Commits. | Tất cả thành viên |
| 🚀 **[CONTRIBUTING.md](CONTRIBUTING.md)** | **Hướng dẫn quy trình sửa code:** Các bước từ kéo code `main`, tạo nhánh, code, chạy `./vendor/bin/pint` & `php artisan test`, commit đến mở Pull Request. | Thành viên khi bắt đầu code |

---

## 🏛 Kiến trúc Hệ thống (Microservices Architecture)

```text
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                          HỆ THỐNG HỖ TRỢ SINH VIÊN (MONOREPO)                          │
└──────────────────────────────────────────┬─────────────────────────────────────────────┘
                                           │
      ┌──────────────────┬─────────────────┼──────────────────┬──────────────────┐
      ▼                  ▼                 ▼                  ▼                  ▼
┌─────────────┐    ┌─────────────┐   ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Module 1   │    │  Module 2   │   │  Module 3   │    │  Module 4   │    │  Module 5   │
│Account Serv.│    │Catalog Serv.│   │Request Serv.│    │Discussions  │    │Report Serv. │
│   :8001     │    │   :8002     │   │   :8003     │    │   :8004     │    │   :8005     │
│(Xác thực,   │    │(Danh mục,PB,│   │(Vòng đời    │    │(Diễn đàn,   │    │(Thống kê,   │
│ User, Role) │    │ biểu mẫu,SLA│   │ Ticket, SLA)│    │ tài liệu SV)│    │ CSAT, PDF)  │
└─────────────┘    └─────────────┘   └─────────────┘    └─────────────┘    └─────────────┘
```

---

## 📦 Danh sách các Dịch vụ (Services)

| Service | Thư mục | Cổng (Port) | Chức năng chính |
| :--- | :--- | :--- | :--- |
| **Module 1 – Account & Auth** | `module1-account-service/` | `:8001` | Xác thực người dùng (Auth/JWT), quản lý tài khoản, phân quyền sinh viên, cán bộ và quản trị viên. |
| **Module 2 – Catalog Service** | `module2-catalog-service/` | `:8002` | Quản lý phòng ban, loại hỗ trợ, cán bộ theo phòng, quy định thời hạn SLA (`sla_days`), biểu mẫu động và FAQ. |
| **Module 3 – Request Service** | `module3-request-service/` | `:8003` | Quản lý toàn bộ vòng đời Ticket: tiếp nhận, máy trạng thái 7 bước, tự động phân công cán bộ, quét SLA, trao đổi & tệp đính kèm, đánh giá hài lòng. |
| **Module 4 – Discussions & Docs** | `module4-discussions-and-documents/` | `:8004` | Diễn đàn trao đổi sinh viên, đăng tin bài thông báo, chia sẻ và quản lý tài liệu học tập. |
| **Module 5 – Report Service** | `report-service/` | `:8005` | Thống kê số liệu, đo lường hiệu suất SLA, xếp hạng cán bộ/phòng ban, khảo sát CSAT, Dashboard biểu đồ trực quan, xuất báo cáo Excel/PDF. |

---

## 🚀 Hướng dẫn Cài đặt & Khởi chạy Toàn bộ Hệ thống

### 1. Yêu cầu môi trường
- PHP >= 8.2 (kèm các extension: `pdo`, `mbstring`, `fileinfo`, `sqlite3` / `pdo_mysql`)
- Composer >= 2.x
- Node.js & NPM
- Cơ sở dữ liệu: MySQL hoặc SQLite

### 2. Khởi chạy từng Microservice

Mỗi service được khởi chạy trên một cửa sổ Terminal (hoặc tab) riêng:

#### Khởi chạy Module 1 (Account Service):
```bash
cd module1-account-service
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001
```

#### Khởi chạy Module 2 (Catalog Service):
```bash
cd module2-catalog-service
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8002
```

#### Khởi chạy Module 3 (Request Service):
```bash
cd module3-request-service
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve --port=8003
```

#### Khởi chạy Module 4 (Discussions & Documents Service):
```bash
cd module4-discussions-and-documents
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8004
```

#### Khởi chạy Module 5 (Report Service):
```bash
cd report-service
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8005
```

#### Khởi chạy API Gateway (cổng vào duy nhất của frontend):
```bash
cd api-gateway
php -S localhost:8000 index.php
```
Frontend gọi `http://localhost:8000/api/...`, Gateway chuyển xuống đúng service. Xem [api-gateway/README.md](api-gateway/README.md).

---

## 🔄 Quy trình Làm việc Git (Git Flow & PR Guidelines)

Nhóm áp dụng mô hình phát triển qua **Pull Request** trên nhánh `main`:

### 1. Đồng bộ mã nguồn mới nhất từ `main`
Trước khi bắt đầu làm tính năng mới:
```bash
git checkout main
git pull origin main
```

### 2. Tạo nhánh làm việc cho tính năng của bạn
```bash
# Đặt tên nhánh theo cấu trúc: feature/<ten-module>-<tinh-nang>
git checkout -b feature/module3-export-excel
```

### 3. Làm việc độc lập trong thư mục module phụ trách
- Thành viên chỉ chỉnh sửa mã nguồn bên trong thư mục module được phân công (ví dụ: `module3-request-service/`).
- Hạn chế tối đa chỉnh sửa thư mục của module khác để tránh phát sinh conflict.

### 4. Kiểm thử trước khi đẩy code
```bash
# Chạy test trong thư mục module của bạn
cd module3-request-service
php artisan test
```

### 5. Commit và Push lên GitHub
```bash
git add module3-request-service/
git commit -m "feat(module3): bo sung tinh nang xuat file excel"
git push origin feature/module3-export-excel
```

### 6. Mở Pull Request trên GitHub
- Truy cập vào repository GitHub: [Student-Support-Ticketing-System](https://github.com/nttlinh100205-ctrl/Student-Support-Ticketing-System).
- Bấm **Compare & pull request** từ nhánh của bạn vào `main`.
- Đội ngũ thành viên thực hiện Code Review và xác nhận merge vào nhánh `main`.
