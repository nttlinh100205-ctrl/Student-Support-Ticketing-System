# Module 3 – Request Service (Quản lý Yêu cầu & Tiếp nhận Hỗ trợ)

Module quản lý toàn bộ vòng đời của yêu cầu hỗ trợ sinh viên (Ticketing System), bao gồm: tiếp nhận, máy trạng thái (State Machine), phân công tự động, giám sát SLA & cảnh báo quá hạn, luồng trao đổi nội bộ/công khai kèm tệp đính kèm, phát hiện trùng lặp, đánh giá hài lòng và cung cấp cả giao diện Web Blade lẫn API RESTful.

---

## 📋 Mục lục

- [Tính năng chính](#-tính-năng-chính)
- [Cài đặt & Khởi chạy](#-cài-đặt--khởi-chạy)
- [Cơ chế Xác thực (Authentication)](#-cơ-chế-xác-thực-authentication)
- [Vòng đời Yêu cầu (State Machine)](#-vòng-đời-yêu-cầu-state-machine)
- [Cơ chế SLA & Giám sát Tự động](#-cơ-chế-sla--giám-sát-tự-động)
- [Trao đổi (Comments) & Tệp đính kèm](#-trao-đổi-comments--tệp-đính-kèm)
- [Chống Trùng lặp & Mẫu nội dung](#-chống-trùng-lặp--mẫu-nội-dung)
- [Đánh giá & Nghiệm thu (Rating)](#-đánh-giá--nghiệm-thu-rating)
- [Danh sách API RESTful](#-danh-sách-api-restful)
- [Giao diện Web Blade](#-giao-diện-web-blade)
- [Kiểm thử (Testing)](#-kiểm-thử-testing)

---

## 🚀 Tính năng chính

1. **Quản lý Vòng đời Ticket (State Machine chặt chẽ):**
   - Đầy đủ 7 trạng thái: `new` (Mới tạo) ➔ `received` (Đã tiếp nhận) ➔ `in_progress` (Đang xử lý) ➔ `waiting_info` (Chờ sinh viên bổ sung) ➔ `resolved` (Chờ sinh viên xác nhận) ➔ `closed` (Đã đóng) / `cancelled` (Đã hủy).
   - Kiểm soát quyền chuyển trạng thái theo đúng vai trò: Sinh viên, Cán bộ xử lý, Trưởng phòng ban, Quản trị viên (Admin).
   - Tự động ghi lại lịch sử chuyển trạng thái (`request_status_histories`) kèm tác nhân thực hiện.

2. **Phân công Cán bộ (Assignment):**
   - **Tự động phân công thông minh (Auto-assign):** Khi tạo ticket mới, hệ thống tự động gán cho cán bộ đang có ít việc nhất (`least loaded staff`) thuộc phòng ban phụ trách.
   - **Phân công thủ công:** Trưởng phòng ban hoặc Admin có thể chỉ định cán bộ xử lý cụ thể.
   - **Điều chuyển phòng ban (Transfer):** Admin có quyền chuyển ticket sang phòng ban khác khi sinh viên gửi nhầm.

3. **Hệ thống SLA (Service Level Agreement):**
   - Tính toán hạn xử lý (`sla_deadline_at`) dựa theo mức độ ưu tiên (`urgent`, `high`, `normal`, `low`) hoặc số ngày quy định từ danh mục (`sla_days`).
   - Cảnh báo sớm (`warning`) khi đã sử dụng hết ngưỡng thời gian quy định (mặc định 75%).
   - Cảnh báo vi phạm (`breached`) khi quá thời hạn giải quyết.
   - Tự động gán cán bộ nếu ticket chưa có người phụ trách sau 24h quá hạn.
   - Artisan Command quét SLA chạy định kỳ hoặc chạy thủ công: `php artisan sla:check`.

4. **Kênh trao đổi tương tác (Comment Thread):**
   - Trao đổi 2 chiều giữa Sinh viên và Cán bộ phụ trách.
   - **Ghi chú nội bộ (`is_internal = true`):** Dành riêng cho cán bộ và lãnh đạo thảo luận; tự động ẩn đối với sinh viên.
   - Đính kèm tệp đa phương tiện an toàn (hình ảnh, tài liệu minh chứng) với cơ chế bảo mật xem trước (`preview`).

5. **Phát hiện Trùng lặp & Hỗ trợ Soạn thảo:**
   - Cảnh báo khi sinh viên gửi các yêu cầu có tiêu đề/nội dung tương tự các ticket đang mở trong hệ thống.
   - Tự động điền mẫu nội dung (`content_template`) chuẩn hóa theo từng loại hỗ trợ giúp sinh viên trình bày rõ ràng.
   - Chức năng nhân bản / gửi lại yêu cầu tương tự (`/requests/{id}/copy`).

6. **Khảo sát Sự Hài lòng (Rating & Feedback):**
   - Sau khi ticket được đóng (`closed`), sinh viên có thể đánh giá từ 1 đến 5 sao và để lại nhận xét chất lượng phục vụ.
   - Nếu chưa hài lòng ở trạng thái giải quyết (`resolved`), sinh viên có quyền yêu cầu xử lý lại (Rework) hoặc mở lại (Reopen) ticket.

---

## 🛠 Cài đặt & Khởi chạy

### Yêu cầu môi trường
- PHP >= 8.2 (kèm các extension: `pdo`, `mbstring`, `fileinfo`, `sqlite3` / `pdo_mysql`)
- Composer
- Node.js & NPM

### Các bước cài đặt

```bash
# 1. Di chuyển vào thư mục module
cd module3-request-service

# 2. Cài đặt các thư viện PHP
composer install

# 3. Tạo file cấu hình môi trường
cp .env.example .env
php artisan key:generate

# 4. Cấu hình cơ sở dữ liệu và nạp dữ liệu mẫu
php artisan migrate --seed

# 5. Cài đặt và build assets giao diện (Tailwind CSS + DaisyUI)
npm install
npm run build

# 6. Khởi chạy máy chủ phát triển
php artisan serve
```

> **Ghi chú về Storage trên Windows/XAMPP:**  
> Hệ thống đã tích hợp sẵn Route fallback phục vụ file trực tiếp tại `/storage/{path}` (`routes/web.php`). Bạn không cần lo lắng về sự cố Symbolic Link trên môi trường Windows / Apache.

---

## 🔐 Cơ chế Xác thực (Authentication)

Hiện tại, Service sử dụng middleware `auth.fake` mô phỏng phiên đăng nhập thông qua HTTP Headers:
- `X-User-Id`: ID của người dùng (ví dụ: `1`, `21`, `99`).
- `X-User-Role`: Vai trò người dùng, gồm 4 nhóm quyền:
  - `student`: Sinh viên (chỉ xem và thao tác trên ticket của chính mình).
  - `staff`: Cán bộ xử lý hỗ trợ (thao tác trên các ticket được phân công hoặc cùng phòng ban).
  - `department_head`: Trưởng phòng ban (quản lý phân công, theo dõi toàn bộ ticket trong phòng).
  - `admin`: Quản trị viên hệ thống (toàn quyền điều chuyển, quản lý tất cả ticket).

Khi Module 1 hoàn tất chuẩn Token JWT, chỉ cần thay thế logic giải mã trong `AuthContextServiceProvider` và `FakeAuthMiddleware` mà không ảnh hưởng tới core nghiệp vụ.

Trên giao diện Web Blade, có sẵn thanh **Chuyển đổi vai trò nhanh** (`/switch-role`) tại góc trang để thuận tiện demo và kiểm thử.

---

## 🔄 Vòng đời Yêu cầu (State Machine)

```text
       [ Sinh viên tạo ]
              │
              ▼
           ┌──────┐       (Hủy)
           │ new  ├─────────────────────► [ cancelled ]
           └──┬───┘
              │ Tiếp nhận / Tự động phân công
              ▼
        ┌───────────┐
        │ received  │
        └───┬───┬───┘
            │   │ Cần thêm tin
            │   ├────────────────────────┐
            │   │                        ▼
            │   │                 ┌──────────────┐
            │   │                 │ waiting_info │
            │   │                 └──────┬───────┘
            │   │                        │ SV bổ sung
            │   │◄───────────────────────┘
            ▼   ▼
       ┌─────────────┐
       │ in_progress │
       └──────┬──────┘
              │ Cán bộ hoàn thành giải quyết
              ▼
         ┌──────────┐  SV yêu cầu làm lại (Rework)
         │ resolved ├─────────────────────────┐
         └───┬───┬──┘                         │
             │   │ SV phản hồi đồng ý         ▼
             │   └──────────────────────► ┌─────────────┐
             │                            │ in_progress │
             │ Cán bộ đóng sau nghiệm thu └─────────────┘
             ▼
         ┌────────┐
         │ closed ├──────► [ Sinh viên đánh giá 1 - 5 sao ]
         └───┬────┘
             │ Sinh viên mở lại (Reopen)
             └──────────────────────────► ┌─────────────┐
                                          │ in_progress │
                                          └─────────────┘
```

---

## ⏱ Cơ chế SLA & Giám sát Tự động

### 1. Cấu hình mốc thời gian (`config/sla.php`)
- **Hạn xử lý theo mức độ ưu tiên:**
  - `urgent`: 4 giờ
  - `high`: 8 giờ
  - `normal`: 24 giờ (1 ngày)
  - `low`: 72 giờ (3 ngày)
- **Ngưỡng cảnh báo sớm:** 75% thời hạn (chuyển cờ `sla_flag` = `warning`).
- **Quá hạn:** Vượt 100% thời hạn (chuyển cờ `sla_flag` = `breached`).

### 2. Quét SLA bằng Artisan Command
Hệ thống cung cấp lệnh quét tự động để kiểm tra toàn bộ ticket đang mở:

```bash
# Quét thực tế và cập nhật cơ sở dữ liệu
php artisan sla:check

# Chạy thử nghiệm (Dry-run) chỉ xem thống kê, không ghi database
php artisan sla:check --dry
```

*(Khuyến nghị cấu hình chạy định kỳ qua Cron Tab / Laravel Scheduler mỗi 5-15 phút).*

---

## 💬 Trao đổi (Comments) & Tệp đính kèm

- **Comment công khai:** Sinh viên và cán bộ cùng xem và gửi phản hồi.
- **Comment nội bộ (`is_internal = 1`):** Cán bộ trao đổi chuyên môn, lãnh đạo chỉ đạo; **bảo mật 100%**, sinh viên hoàn toàn không thấy qua cả Web lẫn API.
- **Tệp đính kèm:** 
  - Hỗ trợ ảnh chụp, tài liệu minh chứng (png, jpg, jpeg, pdf, doc, docx, zip).
  - API upload: `POST /api/requests/{id}/comments/{comment_id}/attachments`.
  - Link xem an toàn: `/requests/{id}/comments/{comment_id}/attachments/{att_id}/preview`.

---

## 🛡 Chống Trùng lặp & Mẫu nội dung

- **Kiểm tra trùng lặp (Duplicate Detection):** Trước khi tạo ticket, hệ thống kiểm tra các yêu cầu chưa đóng của chính sinh viên đó. Nếu tiêu đề hoặc nội dung trùng khớp trên 80%, API sẽ trả cảnh báo kèm mã yêu cầu cũ để tránh gửi rác. Để xác nhận tiếp tục gửi, truyền thêm `confirm_duplicate: true`.
- **Mẫu nội dung (`config/master_data.php`):** Cung cấp sẵn khung nội dung chuẩn theo từng loại thủ tục (Xin bảng điểm, Xác nhận sinh viên, Báo hỏng cơ sở vật chất...) khi sinh viên bấm chọn loại dịch vụ.

---

## ⭐ Đánh giá & Nghiệm thu (Rating)

- Khi ticket đạt trạng thái `closed`, sinh viên có thể đánh giá chất lượng phục vụ:
  - **Điểm số:** 1 đến 5 sao (`rating_score`).
  - **Ý kiến đóng góp:** Ghi chú cảm nhận (`rating_comment`).
  - Ràng buộc: Mỗi ticket chỉ được đánh giá duy nhất một lần bởi chính sinh viên tạo yêu cầu.

---

## 📡 Danh sách API RESTful

Tất cả các API tuân thủ API Contract chuẩn:
```json
{ "success": true, "data": { ... }, "message": null }
{ "success": false, "message": "Thông báo lỗi", "errors": { ... } }
```

Yêu cầu Header: `X-User-Id: {id}`, `X-User-Role: {role}`

### Quản lý Ticket (`/api/requests`)

| Method | Endpoint | Quyền hạn | Mô tả |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/requests` | Tất cả | Lấy danh sách ticket (lọc theo status, department, search, phân trang) |
| `POST` | `/api/requests` | Student, Admin | Tạo yêu cầu hỗ trợ mới (tự động phân công, kiểm tra trùng lặp) |
| `GET` | `/api/requests/{id}` | Có liên quan | Chi tiết ticket, tiến trình, cán bộ xử lý |
| `PUT` | `/api/requests/{id}` | Chủ sở hữu / Cán bộ | Cập nhật thông tin (tiêu đề, nội dung khi chưa xử lý) |
| `PUT` | `/api/requests/{id}/status` | Staff, Dept Head | Chuyển trạng thái ticket theo máy trạng thái |
| `PUT` | `/api/requests/{id}/assign` | Dept Head, Admin | Phân công cán bộ xử lý ticket |
| `PUT` | `/api/requests/{id}/transfer` | Admin | Điều chuyển ticket sang phòng ban khác |
| `PUT` | `/api/requests/{id}/cancel` | Sinh viên tạo | Hủy yêu cầu hỗ trợ |
| `DELETE` | `/api/requests/{id}` | Admin | Xóa yêu cầu (nếu cần dọn dẹp) |
| `GET` | `/api/requests/{id}/history` | Có liên quan | Xem toàn bộ nhật ký thay đổi trạng thái ticket |

### Trao đổi & Tệp đính kèm (`/api/requests/{id}/comments`)

| Method | Endpoint | Quyền hạn | Mô tả |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/requests/{id}/comments` | Có liên quan | Lấy danh sách bình luận (tự lọc bỏ nội bộ nếu là SV) |
| `POST` | `/api/requests/{id}/comments` | Có liên quan | Thêm bình luận mới (hỗ trợ cờ `is_internal`) |
| `DELETE` | `/api/requests/{id}/comments/{comment}` | Người gửi / Admin | Xóa bình luận |
| `POST` | `/api/requests/{id}/comments/{cId}/attachments` | Người gửi | Tải tệp đính kèm lên bình luận |
| `GET` | `/api/requests/{id}/comments/{cId}/attachments/{aId}/preview` | Công khai / Hợp lệ | Xem trước hoặc tải tệp đính kèm |

### Giám sát SLA (`/api/sla`)

| Method | Endpoint | Quyền hạn | Mô tả |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/sla/notifications` | Nhân viên, Cán bộ | Lấy danh sách thông báo nhắc nhở SLA cá nhân |
| `GET` | `/api/sla/tickets` | Dept Head, Admin | Danh sách các ticket vi phạm hoặc sắp quá hạn SLA |

---

## 🖥 Giao diện Web Blade

Bên cạnh REST API, module cung cấp bộ giao diện hoàn chỉnh xây dựng bằng Laravel Blade + Tailwind CSS + DaisyUI:

- **Danh sách yêu cầu (`/requests`):** Bộ lọc thông minh theo trạng thái, phòng ban, từ khóa tìm kiếm; hiển thị thẻ trạng thái trực quan, cờ cảnh báo SLA màu sắc rõ ràng.
- **Tạo yêu cầu mới (`/requests/create`):** Tự động điền template mẫu khi chọn loại hỗ trợ, cảnh báo trùng lặp ngay trên form.
- **Chi tiết & Xử lý ticket (`/requests/{id}`):**
  - Timeline hiển thị quá trình xử lý và nhật ký trạng thái.
  - Hộp trao đổi tin nhắn trực tiếp phân biệt rõ màu giữa phản hồi sinh viên và trao đổi nội bộ.
  - Các nút hành động nghiệp vụ (Tiếp nhận, Yêu cầu bổ sung, Hoàn thành, Phân công, Điều chuyển) hiển thị thông minh tùy theo quyền người dùng đang đăng nhập.
  - Hộp đánh giá sao nghiệm thu chất lượng sau khi hoàn thành.
- **Sao chép ticket (`/requests/{id}/copy`):** Tái sử dụng dữ liệu từ ticket cũ để tạo yêu cầu mới nhanh chóng.

---

## 🧪 Kiểm thử (Testing)

Dự án có độ phủ kiểm thử cao cho toàn bộ luồng nghiệp vụ API và Web:

```bash
# Chạy toàn bộ bộ kiểm thử tự động
php artisan test
```

### Kết quả kiểm thử hiện tại:
- **37 Test Cases**, **124 Assertions** — **100% PASS**
  - `Tests\Feature\RequestApiTest`: Kiểm thử xác thực Header, phân quyền trạng thái, tự động gán cán bộ, điều chuyển, phát hiện trùng lặp, SLA dry-run, ẩn comment nội bộ, template mẫu.
  - `Tests\Feature\CommentAttachmentTest`: Kiểm thử upload tệp đính kèm an toàn, phân quyền preview.
  - `Tests\Feature\RequestRatingTest`: Kiểm thử đánh giá 1-5 sao sau khi đóng ticket, xác thực quyền chủ sở hữu.

### Kiểm tra định dạng mã nguồn (Code Style):
```bash
./vendor/bin/pint --test    # Kiểm tra quy chuẩn PSR-12 / Laravel
./vendor/bin/pint           # Tự động sửa định dạng code
```
