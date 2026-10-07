# Tài Liệu Chuẩn Hóa Dự Án — API Contract Chung
> **Hệ Thống Hỗ Trợ Sinh Viên (Student Support Ticketing System)**

Tài liệu này là **bản thỏa thuận chung (API Contract)** giữa tất cả các thành viên phát triển hệ thống. Mọi module (Module 1, 2, 3, 4, 5) bắt buộc phải tuân theo các quy định trong tài liệu này để đảm bảo toàn bộ hệ thống tích hợp mượt mà, không xung đột.

---

## 1. Bức Tranh Tổng Thể Hệ Thống

```text
Người dùng (sinh viên / cán bộ) mở trình duyệt
 │
 ▼
┌────────────────────────────────────────────────────────┐
│ React Frontend          (chạy ở http://localhost:3000) │
└───────────────────────────┬────────────────────────────┘
                            │ gọi API bằng HTTP (axios/fetch)
                            ▼
┌────────────────────────────────────────────────────────┐
│ API Gateway             (chạy ở http://localhost:8000) │ — điều hướng request
└───────────────────────────┬────────────────────────────┘   tới đúng service bên dưới
                            │
       ┌────────────┬───────┴─────┬────────────┬─────────────┐
       ▼            ▼             ▼            ▼             ▼
┌─────────────┐┌─────────────┐┌─────────────┐┌─────────────┐┌─────────────┐
│ Auth Service││ Org Service ││Request Serv.││ File Serv.  ││Report Serv. │
│   :8001     ││   :8002     ││   :8003     ││   :8004     ││   :8005     │
│  (Module 1) ││  (Module 2) ││  (Module 3) ││  (Module 4) ││  (Module 5) │
└──────┬──────┘└─────────────┘└─────────────┘└─────────────┘└─────────────┘
       │
       └── là NƠI DUY NHẤT biết mật khẩu, phát hành "thẻ ra vào" (JWT)
           cho user. Các service khác không biết mật khẩu, chỉ biết
           kiểm tra "thẻ ra vào" đó có hợp lệ hay không.
```

> **Điểm mấu chốt:** Mỗi ô vuông trong hình trên là **1 project Laravel chạy độc lập**, có **1 database riêng**. Các service **không dùng chung code, không dùng chung database** — chỉ nói chuyện với nhau qua HTTP request (giống như cách Postman gọi API).

---

## 2. Cơ Chế Xác Thực Bằng JWT (JSON Web Token)

### 2.1. Vấn đề cần giải quyết
Sinh viên đăng nhập ở **Module 1 (Auth)**. Sau đó sinh viên bấm *"Tạo yêu cầu hỗ trợ"* — request này đi thẳng tới **Module 3 (Request)** mà không đi qua Module 1 nữa. 

Vậy làm sao Module 3 biết người đang gọi API là ai? Có phải sinh viên hợp lệ đã đăng nhập không, hay là người lạ?

### 2.2. Giải pháp: JWT — "Thẻ ra vào có chữ ký số"
Tương tự thẻ sinh viên: trên thẻ có ảnh, tên, MSSV và có con dấu của trường. Bác bảo vệ ở cổng chỉ cần nhìn con dấu là biết thẻ thật, không cần gọi điện hỏi Phòng Đào tạo.

**Cách JWT vận hành:**
1. Sinh viên đăng nhập ở **Module 1** ➔ Module 1 kiểm tra email/password đúng ➔ "đóng dấu" (ký) ra 1 chuỗi JWT chứa thông tin `user_id`, `role`...
2. Frontend (React) lưu chuỗi JWT này lại trong bộ nhớ trình duyệt.
3. Mỗi lần gọi API tới bất kỳ module nào khác (Module 2, 3, 4, 5), Frontend đính kèm chuỗi JWT này vào Header.
4. Module nhận request (VD Module 3) tự kiểm tra chữ ký bằng **Public Key** dùng chung — nếu khớp thì tin tưởng thông tin bên trong, **không cần gọi hỏi lại Module 1**.

➔ Giúp từng service tự xác thực nhanh chóng, không gây "nghẽn cổ chai" tại Auth Service.

---

### 2.3. Cấu trúc Payload bên trong JWT (Bắt buộc dùng đúng 7 field này)

```json
{
  "sub": 12,
  "role": "staff",
  "department_id": 3,
  "email": "canbo01@university.edu.vn",
  "full_name": "Nguyễn Văn A",
  "iat": 1757930000,
  "exp": 1757933600
}
```

#### Giải thích chi tiết từng field:

| Field | Nghĩa là gì | Ví dụ |
| :--- | :--- | :--- |
| `sub` | ID của user (subject) — chính là ID trong bảng `users` bên Module 1. | `12` |
| `role` | Vai trò của user — **chỉ có 4 giá trị cố định**: | `"student"`, `"staff"`, `"department_head"`, `"admin"` |
| `department_id` | Nếu user là cán bộ, đây là phòng ban họ trực thuộc. Nếu là sinh viên / admin thì để `null`. | `3` hoặc `null` |
| `email` | Email của user, để hiển thị nhanh không cần gọi API hỏi lại. | `"canbo01@university.edu.vn"` |
| `full_name` | Họ tên người dùng, để ghi log (VD: *"Nguyễn Văn A đã đổi trạng thái"*). | `"Nguyễn Văn A"` |
| `iat` | Thời điểm token được tạo ra (*issued at*), dạng unix timestamp. | `1757930000` |
| `exp` | Thời điểm token hết hạn (*expire*). | `1757933600` |

> **Phân công trách nhiệm:**
> - **Người làm Module 1:** Khi login thành công, JWT trả về phải chứa **đúng 7 field trên**, viết đúng tên (chữ thường, dấu gạch dưới `_`).
> - **Người làm Module 2, 3, 4, 5:** Chỉ cần viết code giải mã và đọc lại đúng 7 field này (không tự ý đặt tên khác).

---

### 2.4. Cách gửi JWT khi gọi API
```http
GET /api/requests
Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEyLCJyb2xlIjoic3R1ZGVudCJ9...
```
*(Luôn có chữ `Bearer` + một dấu cách, rồi mới tới chuỗi token).*

---

### 2.5. Giai đoạn Development: Chưa có Module 1 thì test kiểu gì?

Trong lúc Module 1 chưa hoàn thiện JWT thật, cả nhóm **giả lập xác thực bằng HTTP Header thường** (không mã hóa, dùng để dev & test nhanh qua Postman):

```http
X-User-Id: 12
X-User-Role: staff
X-Department-Id: 3
```

Mỗi module viết 1 middleware nhỏ (ví dụ `FakeAuthMiddleware` + `AuthContext`) đọc 3 header này. Đặt tên hàm thống nhất: `userId()`, `role()`, `departmentId()`. Khi Module 1 xong JWT thật, bạn chỉ việc đổi nơi đọc dữ liệu mà không cần sửa logic nghiệp vụ.

---

## 3. Luồng Chạy Thực Tế (End-to-End Flow)

#### Bước 1 — Sinh viên đăng nhập (Gọi Module 1)
```http
POST http://localhost:8001/api/auth/login
Content-Type: application/json

{
  "email": "sv001@university.edu.vn",
  "password": "matkhau123"
}
```
**Module 1 trả về:**
```json
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": 12,
      "full_name": "Trần Thị B",
      "email": "sv001@university.edu.vn",
      "role": "student"
    }
  },
  "message": null
}
```

#### Bước 2 — Frontend lưu token và gọi tạo yêu cầu (Gọi Module 3)
```http
POST http://localhost:8003/api/requests
Content-Type: application/json
Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...

{
  "department_id": 3,
  "support_type_id": 2,
  "title": "Xin xác nhận sinh viên",
  "content": "Em cần giấy xác nhận sinh viên để làm thủ tục vay vốn."
}
```

#### Bước 3 — Module 3 xử lý nội bộ
1. Middleware verify JWT ➔ lấy được `sub = 12`, `role = "student"`.
2. Kiểm tra quyền: vì `role = "student"` nên được phép tạo yêu cầu (`POST /api/requests`).
3. Lưu `student_id = 12` vào bảng `requests`.
4. Trả kết quả về cho Frontend.

#### Bước 4 — Module 3 trả Response
```json
{
  "success": true,
  "data": {
    "id": 101,
    "code": "YC-2026-000101",
    "student_id": 12,
    "department_id": 3,
    "support_type_id": 2,
    "title": "Xin xác nhận sinh viên",
    "status": "new",
    "created_at": "2026-09-15T03:12:00.000000Z"
  },
  "message": null
}
```

> **Lưu ý:** Module 3 hoàn toàn không gọi sang Module 1 để hỏi *"User 12 có tồn tại không?"* — vì dữ liệu đã nằm sẵn trong token đã được ký số!

---

## 4. Chuẩn Format Response Bắt Buộc

### 4.1. Vì sao cần ép cùng một format?
Nếu Module 1 trả `{ "user": {...} }`, Module 3 trả `{ "data": {...} }`, Module 5 trả mảng trần `[...]` — Frontend sẽ phải viết 5 kiểu xử lý khác nhau cho 5 module, rất dễ phát sinh lỗi. Ép tất cả vào cùng 1 khuôn giúp Frontend viết **1 hàm xử lý duy nhất**.

### 4.2. Khuôn chuẩn dữ liệu

#### Khi Thành công:
```json
{
  "success": true,
  "data": { ... },
  "message": null
}
```
- `success`: luôn là `true`.
- `data`: dữ liệu thực tế (object hoặc mảng danh sách).
- `message`: để `null` khi thành công thông thường, hoặc điền chuỗi thông báo nếu cần (VD: `"Đã gửi email thành công"`).

#### Khi Thất bại (Lỗi):
```json
{
  "success": false,
  "message": "Nội dung lỗi dễ hiểu bằng tiếng Việt",
  "errors": { ... }
}
```
- `success`: luôn là `false`.
- `message`: thông báo lỗi thân thiện để hiển thị cho người dùng.
- `errors`: object liệt kê chi tiết các trường bị lỗi validate (HTTP 422), nếu không có lỗi validate thì bỏ qua hoặc để `null`.

---

### 4.3. Class dùng chung cho MỌI module Laravel

Tạo file `app/Http/Responses/ApiResponse.php` và copy đoạn code này vào cả 5 module:

```php
<?php

namespace App\Http\Responses;

class ApiResponse
{
    public static function success($data = null, ?string $message = null, int $status = 200)
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    public static function error(string $message, int $status = 400, ?array $errors = null)
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
```

#### Cách sử dụng trong Controller:
```php
use App\Http\Responses\ApiResponse;

// Thành công (Tạo mới -> 201)
public function store(StoreRequestRequest $request)
{
    $data = $request->validated();
    $item = $this->workflow->create($data, $this->auth->userId());

    return ApiResponse::success($item, message: null, status: 201);
}

// Lỗi nghiệp vụ -> 409
public function updateStatus(UpdateStatusRequest $request, SupportRequest $supportRequest)
{
    try {
        $updated = $this->workflow->changeStatus($supportRequest, $request->validated('status'));
        return ApiResponse::success($updated);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return ApiResponse::error($e->getMessage(), status: 409);
    }
}
```

#### ❌ Các lỗi thường gặp cần tránh:
- ❌ **Sai:** Trả thẳng model không bọc: `{ "id": 101, "title": "..." }`
- ❌ **Sai:** Đặt tên field ngoài cùng khác nhau: `{ "result": {...} }` hoặc `{ "payload": {...} }`
- ✅ **Đúng:** Luôn bọc trong `success` / `data` / `message` bằng `ApiResponse::success()` và `ApiResponse::error()`.

---

## 5. Quy Ước Đặt Tên Endpoint API (RESTful)

### Quy tắc nhớ nhanh:
1. **Danh từ số nhiều:** `/requests` (không dùng `/request`).
2. **Chữ thường, nối bằng dấu gạch ngang (`kebab-case`):** `/support-types` (không dùng `/supportTypes`).
3. **Hành động đặc biệt gắn sau ID:** `/requests/{id}/cancel` (không dùng `/cancel-request/{id}`).

### Bảng ví dụ Endpoint chuẩn theo từng Module:

| Module | Phương thức & URL mẫu | Mô tả |
| :--- | :--- | :--- |
| **Module 1 (Auth)** | `POST /api/auth/login`<br>`GET /api/users/{id}`<br>`PUT /api/users/{id}/status` | Đăng nhập<br>Xem thông tin user<br>Đổi trạng thái tài khoản |
| **Module 2 (Org)** | `GET /api/departments`<br>`POST /api/support-types`<br>`GET /api/departments/{id}/staff` | Lấy danh sách phòng ban<br>Tạo loại hỗ trợ mới<br>Lấy cán bộ theo phòng |
| **Module 3 (Request)** | `POST /api/requests`<br>`PUT /api/requests/{id}/assign`<br>`PUT /api/requests/{id}/cancel` | Tạo ticket mới<br>Phân công cán bộ<br>Hủy yêu cầu |
| **Module 4 (File & Comment)**| `POST /api/requests/{id}/comments`<br>`POST /api/requests/{id}/attachments` | Thêm bình luận trao đổi<br>Tải tệp đính kèm |
| **Module 5 (Report)** | `GET /api/reports/statistics`<br>`GET /api/reports/export` | Xem số liệu thống kê<br>Xuất báo cáo Excel/CSV |

---

## 6. Phân Công & Hướng Dẫn Thực Hiện Từng Module

| Bạn phụ trách | Bắt buộc đọc | Việc phải làm theo tài liệu |
| :--- | :--- | :--- |
| **Module 1 (Auth)** | Toàn bộ Mục 2 | Khi đăng nhập thành công, JWT phải chứa đúng 7 field ở Mục 2.3. Mọi response dùng `ApiResponse`. |
| **Module 2 (Org)** | Mục 2.5, Mục 4, Mục 5 | Dùng middleware đọc header giả `X-User-Id` / `X-User-Role`. Trả response theo `ApiResponse`. |
| **Module 3 (Request)** | Đã áp dụng | Đã tích hợp chuẩn `AuthContext` + `ApiResponse` + `State Machine`. |
| **Module 4 (File/Comment)**| Mục 2.5, Mục 4, Mục 5 | Khi tải/xem file, gọi `GET /api/requests/{id}` sang Module 3 để kiểm tra quyền trước khi cho tải. |
| **Module 5 (Report)** | Mục 2.5, Mục 4, Mục 5 | Gọi `GET /api/requests` sang Module 3 và Module 2 để lấy dữ liệu tính toán thống kê. |

---

## 7. Các Câu Hỏi Thường Gặp (FAQ)

- **Q: Nếu module tôi cần gọi API sang module khác mà module đó chưa làm xong thì sao?**  
  👉 **A:** Tự tạo dữ liệu giả (**Mock Data**). Ví dụ Module 5 chưa có API Module 3 thật thì tạo 1 file JSON giả lập mẫu response như Mục 3 để code tiếp giao diện và logic của mình.

- **Q: Field trong JWT tôi thấy thiếu thông tin so với nhu cầu module tôi thì sao?**  
  👉 **A:** **KHÔNG ĐƯỢC tự ý thêm field**. Báo lại nhóm trong buổi họp, thảo luận và sửa tài liệu này 1 lần để tất cả cùng đồng bộ.

- **Q: Module tôi bị lỗi thì trả về HTTP Status Code nào?**  
  - `400`: Lỗi client gửi sai định dạng chung.
  - `401`: Chưa đăng nhập / thiếu token xác thực.
  - `403`: Không có quyền truy cập (sai vai trò).
  - `404`: Không tìm thấy dữ liệu.
  - `409`: Sai logic nghiệp vụ (ví dụ: chuyển sai trạng thái state machine, xóa phòng ban còn dữ liệu).
  - `422`: Sai dữ liệu nhập validate form (kèm mảng `errors`).
  - `500`: Lỗi server nội bộ.

---

## 8. Bảng Thuật Ngữ (Glossary)

| Thuật ngữ | Giải nghĩa ngắn gọn |
| :--- | :--- |
| **API Contract** | Bản thỏa thuận về format dữ liệu gửi/nhận giữa các module ("luật chơi chung"). |
| **JWT (JSON Web Token)** | Chuỗi ký tự đóng vai trò "thẻ ra vào", chứng minh người dùng đã đăng nhập và có chữ ký chống giả mạo. |
| **Payload** | Phần dữ liệu thực tế nằm bên trong JWT (`sub`, `role`, `email`...). |
| **Middleware** | Đoạn code chạy kiểm tra trước khi request tới Controller (VD: kiểm tra JWT, check role). |
| **Soft Reference** | Lưu ID của bảng ở service khác nhưng **không dùng Foreign Key thật** trong database (vì mỗi service có DB riêng). |
| **Endpoint** | Một đường dẫn API cụ thể (VD: `POST /api/requests`). |
| **Status Code** | Mã 3 chữ số HTTP thông báo kết quả thực thi (200, 201, 400, 403, 404, 409, 422...). |
| **Public / Private Key** | Cặp khóa mã hóa: Module 1 giữ Private Key để ký token, các module khác dùng Public Key để kiểm tra chữ ký. |
