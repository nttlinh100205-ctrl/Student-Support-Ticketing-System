# Module 2 – Catalog Service (Danh mục & tổ chức)

Quản lý phòng ban, loại hỗ trợ, cán bộ theo phòng ban, và phần mở rộng:

- **SLA theo loại yêu cầu**: mỗi loại hỗ trợ có `sla_days` (số ngày xử lý dự kiến). Module 3 dùng để tính hạn và cảnh báo quá hạn.
- **Biểu mẫu theo loại yêu cầu**: mỗi loại quy định các trường cần nhập và giấy tờ cần đính kèm (kiểu `file`).
- **FAQ** theo phòng ban / loại yêu cầu, hiển thị cho sinh viên trước khi gửi để giảm yêu cầu trùng.

## Cài đặt

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Mở `/catalog` (sinh viên tra cứu) hoặc `/admin/departments` (quản trị). Đổi vai trò Admin / Sinh viên ở cuối thanh bên.

### Dữ liệu demo (tùy chọn)

`migrate --seed` chỉ nạp dữ liệu cơ bản. Để có đủ 5 phòng ban, 8 loại hỗ trợ kèm SLA, biểu mẫu và FAQ dùng khi demo:

```bash
php artisan db:seed --class=Module2DemoSeeder
```

Seeder này không chạy tự động, chạy lại nhiều lần không tạo trùng (tìm theo mã). Không tạo tài khoản (bảng `users` thuộc Module 1).
SLA và nội dung chỉ để minh họa. Module khác nên tham chiếu phòng ban / loại hỗ trợ theo **mã** (`KT`, `PKBT`…), không theo ID.

## Xác thực

Giống Module 3: mọi API cần header `X-User-Id` và `X-User-Role` (`admin`, `student`, `staff`, `department_head`).
Khi Module 1 có JWT thật, chỉ cần đổi `FakeAuthMiddleware` và binding trong `AuthContextServiceProvider`.

## API cho các module khác (`auth.fake`)

| Method | URL | Mô tả |
|---|---|---|
| GET | `/api/v1/catalog/departments` | Phòng ban đang hoạt động |
| GET | `/api/v1/catalog/support-types?department_id=` | Loại hỗ trợ đang hoạt động (kèm `sla_days`) |
| GET | `/api/v1/catalog/support-types/{id}/form` | Thông tin loại + `sla_days` + các trường biểu mẫu |
| POST | `/api/v1/catalog/support-types/{id}/validate` | Kiểm tra `values[field_key]` theo biểu mẫu: 200 nếu hợp lệ, 422 kèm lỗi theo tên trường |
| GET | `/api/v1/catalog/support-types/{id}/faqs` | FAQ chung của phòng + FAQ riêng của loại |
| GET | `/api/v1/catalog/departments/{id}/faqs` | FAQ theo phòng ban |

Module 3 nên gọi `.../validate` trước khi lưu yêu cầu. Trường kiểu `file` nhận tệp tải lên hoặc tên / mã tệp đã lưu ở Module 3.

## API quản trị (`role:admin`)

- Phòng ban: `GET|POST /api/v1/admin/departments`, `GET|PUT|DELETE /api/v1/admin/departments/{id}`
- Cán bộ: `GET /api/v1/admin/departments/{id}/staff`, `PUT /api/v1/admin/departments/{id}/staff/{userId}` (`role`: `STAFF` | `DEPARTMENT_HEAD`), `GET /api/v1/admin/staff-candidates`
- Loại hỗ trợ: `GET|POST /api/v1/admin/support-types`, `GET|PUT|DELETE /api/v1/admin/support-types/{id}` (có `sla_days`)
- Biểu mẫu: `GET|POST /api/v1/admin/support-types/{id}/fields`, `GET|PUT /.../fields/{fieldId}`, `PUT /.../fields/{fieldId}/status`
- FAQ: `GET|POST /api/v1/admin/faqs`, `GET|PUT /api/v1/admin/faqs/{id}`, `PUT /api/v1/admin/faqs/{id}/status`

Không xóa được phòng ban / loại hỗ trợ đã có yêu cầu: bảng `requests` của Module 3 được kiểm tra khi hai service dùng chung database.

## Kiểm thử

```bash
php artisan test
```
