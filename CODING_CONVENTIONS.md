# Coding Convention Chung — Hệ Thống Hỗ Trợ Sinh Viên

Tài liệu này quy định phong cách viết code (Coding Style) và quy trình làm việc chung cho toàn bộ 5 thành viên trong nhóm.

> **Mục tiêu:** Code của 5 người nhìn như do **1 người viết**, dễ đọc, dễ bảo trì, dễ Review và giảm tối đa xung đột khi gộp mã nguồn.

---

## 1. Quy Tắc Đặt Tên (Naming Convention)

### 1.1. Luôn dùng Tiếng Anh trong mã nguồn
Trong code PHP/Laravel, **luôn dùng tiếng Anh**, tuyệt đối không dùng tiếng Việt (kể cả không dấu).  
*(Comment giải thích và Commit message thì dùng tiếng Việt bình thường).*

| Đối tượng | Quy ước (Convention) | Ví dụ ĐÚNG | Ví dụ SAI |
| :--- | :--- | :--- | :--- |
| **Tên Class** | `PascalCase`, danh từ số ít | `RequestController`, `SupportRequest` | `requestController`, `Request_Controller` |
| **Tên Method / Hàm** | `camelCase`, động từ + danh từ | `changeStatus()`, `getUserById()` | `ChangeStatus()`, `change_status()` |
| **Tên Biến** | `camelCase` | `$studentId`, `$departmentList` | `$student_id`, `$DanhSachPhongBan` |
| **Hằng số (Constant)** | `UPPER_SNAKE_CASE` | `MAX_FILE_SIZE_MB`, `DEFAULT_SLA_DAYS` | `maxFileSize`, `default_sla` |
| **Tên Bảng Database** | `snake_case`, danh từ số nhiều | `requests`, `request_assignments` | `Request`, `requestAssignment` |
| **Tên Cột Database** | `snake_case` | `student_id`, `created_at` | `studentId`, `StudentID` |
| **Tên Route / URL** | `kebab-case`, số nhiều | `/support-types`, `/requests` | `/supportTypes`, `/Request` |
| **Tên File Class** | Trùng tên class | `RequestController.php` | `request_controller.php` |

---

### 1.2. Tên biến phải rõ nghĩa, tránh viết tắt khó hiểu
- ❌ **Sai:** `$sv`, `$dept`, `$req`, `function proc()`
- ✅ **Đúng:** `$student`, `$department`, `$request`, `function processRequest()`
- *Ngoại lệ chấp nhận viết tắt phổ biến:* `$id`, `$db`, biến ngắn trong vòng lặp `foreach ($items as $item)`.

### 1.3. Biến Boolean luôn có tiền tố `is` / `has` / `can`
- ✅ **Đúng:** `$isActive`, `$hasPermission`, `$canEdit`, `$isInternal`
- ❌ **Sai:** `$active`, `$permission`, `$edit`

---

## 2. Cấu Trúc Thư Mục Chuẩn trong 1 Module Laravel

Tất cả các module (`module1-account-service`, `module2-catalog-service`, `module3-request-service`, `report-service`...) đều tuân theo cùng 1 khung thư mục:

```text
app/
├── Contracts/              # Interface trừu tượng (VD: AuthContextInterface.php)
├── Enums/                  # Enum trạng thái, mức độ ưu tiên, vai trò
├── Http/
│   ├── Controllers/
│   │   └── Api/            # REST Controller — CHỈ đặt trong Api/, không để ngoài
│   ├── Middleware/         # Middleware lọc request
│   ├── Requests/           # Form Request classes chuyên validate (StoreRequestRequest...)
│   └── Resources/          # API Resource format dữ liệu trả về (nếu dùng)
├── Models/                 # Eloquent Models
├── Providers/              # Service Providers
├── Services/               # Toàn bộ Business Logic đặt ở đây (không viết trong Controller)
│   └── Auth/               # Sub-namespace nếu nghiệp vụ phức tạp
database/
├── migrations/             # Migration tạo bảng
├── factories/              # Factory sinh dữ liệu mẫu
└── seeders/                # Seeder khởi tạo dữ liệu
routes/
└── api.php                 # Toàn bộ Route API
tests/
├── Unit/                   # Test Service / Logic thuần
└── Feature/                # Test HTTP Request / Response qua route
```

> **Quy tắc:** Không tự ý tạo thêm thư mục lạ (như `app/Helpers/`, `app/Core/`) nếu chưa thống nhất với nhóm.

---

## 3. Nguyên Tắc: Controller Mỏng — Service Dày

Đây là **quy tắc quan trọng nhất** trong dự án:

- **Controller chỉ làm nhiệm vụ điều phối:** Nhận Request ➔ gọi Service ➔ trả về `ApiResponse`.
- **Mỗi hàm trong Controller không quá ~15 dòng code.**
- **Toàn bộ logic nghiệp vụ, tính toán, trạng thái phải nằm trong Service.**

### ❌ Code SAI (Controller ôm hết logic — khó test, khó tái sử dụng):
```php
public function updateStatus(Request $request, $id)
{
    $supportRequest = SupportRequest::find($id);
    if (!$supportRequest) {
        return response()->json(['message' => 'Not found'], 404);
    }

    $newStatus = $request->input('status');
    $allowed = [
        'new' => ['received', 'cancelled'],
        'received' => ['in_progress', 'cancelled'],
    ];

    if (!in_array($newStatus, $allowed[$supportRequest->status] ?? [])) {
        return response()->json(['message' => 'Invalid transition'], 409);
    }

    $supportRequest->status = $newStatus;
    $supportRequest->save();
    // ... gửi mail, ghi log, notify nhét hết vào đây...
    return response()->json($supportRequest);
}
```

### ✅ Code ĐÚNG (Controller mỏng, logic nằm ở Service):
```php
// Controller — gọn gàng, rõ ràng
public function updateStatus(UpdateStatusRequest $request, SupportRequest $supportRequest)
{
    $updated = $this->workflow->changeStatus(
        $supportRequest,
        $request->validated('status'),
        $this->auth->userId(),
        $request->validated('note')
    );

    return ApiResponse::success($updated);
}

// Service — chứa toàn bộ logic, test được độc lập
class RequestWorkflowService
{
    public function changeStatus(SupportRequest $request, string $toStatus, int $changedBy, ?string $note = null): SupportRequest
    {
        // Kiểm tra state machine hợp lệ
        // Ném ValidationException nếu sai trạng thái (Controller bắt và trả 409)
        // Ghi lịch sử RequestStatusHistory
        // Trả về model sau khi cập nhật
    }
}
```

---

## 4. Form Request — Validate Dữ Liệu Đúng Chỗ

Không gọi `$request->validate([...])` trực tiếp trong Controller. Hãy tạo riêng **Form Request**:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Phân quyền xử lý riêng ở Policy / Service
    }

    public function rules(): array
    {
        return [
            'department_id'   => 'required|integer',
            'support_type_id' => 'required|integer',
            'title'           => 'required|string|max:255',
            'content'         => 'required|string',
            'priority'        => 'nullable|in:low,normal,high,urgent',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề yêu cầu.',
            'department_id.required' => 'Vui lòng chọn phòng ban.',
        ];
    }
}
```

> **Quy tắc đặt tên Form Request:** `<Hành động><Resource>Request`  
> Ví dụ: `StoreRequestRequest`, `UpdateStatusRequest`, `AssignStaffRequest`.

---

## 5. Comment & Docblock

- **Docblock:** Chỉ viết cho các hàm public quan trọng trong Service để giải thích **TẠI SAO**, không lặp lại điều code đã tự nói rõ:
  ```php
  /**
   * Đổi trạng thái yêu cầu theo state machine. Ném ValidationException
   * nếu transition không hợp lệ — Controller cần catch để trả 409.
   */
  public function changeStatus(SupportRequest $request, string $toStatus, int $changedBy, ?string $note = null): SupportRequest
  ```
- **Không comment điều hiển nhiên:**
  ```php
  // Sai — thừa thãi
  // lấy id của student
  $studentId = $request->student_id;
  ```
- **Dùng `// TODO:`** cho những việc cần làm sau này (dễ tìm lại bằng `Ctrl + Shift + F`):
  ```php
  // TODO: gọi sang Auth Service lấy full_name khi Module 1 hoàn tất
  ```

---

## 6. Format Code Tự Động — Laravel Pint

Để tránh việc tranh cãi về khoảng trắng, dấu ngoặc nhọn, thụt dòng, tất cả thành viên sử dụng **Laravel Pint** (chuẩn PSR-12):

```bash
# Di chuyển vào thư mục module của bạn
cd module3-request-service

# Tự động định dạng lại toàn bộ code chuẩn đẹp
./vendor/bin/pint

# Chỉ kiểm tra xem có file nào sai chuẩn không
./vendor/bin/pint --test
```

> **Quy tắc bắt buộc:** Luôn chạy `./vendor/bin/pint` trước khi `git commit`.

---

## 7. Quy Tắc Commit Message (Conventional Commits)

Format commit bắt buộc:
```text
<loại>(<phạm vi>): <mô tả ngắn bằng tiếng Việt>
```

### Các tiền tố (`loại`):
- `feat`: Thêm tính năng mới.
- `fix`: Sửa lỗi (bug).
- `refactor`: Sửa cấu trúc code, tối ưu hóa (không đổi hành vi bên ngoài).
- `test`: Thêm hoặc cập nhật bài test tự động.
- `docs`: Cập nhật tài liệu (README, Markdown).
- `chore`: Cấu hình, cài package, dọn dẹp.

### Ví dụ đúng:
- `feat(module3): them API doi trang thai yeu cau`
- `fix(module3): sua loi state machine cho phep huy khi da closed`
- `refactor(module2): tach logic assign sang DepartmentStaffService`
- `test(module3): bo sung test case kiem thu SLA canh bao`
- `docs(monorepo): cap nhat API_CONTRACT va CODING_CONVENTIONS`

> **Quy tắc:** Mỗi commit nên giải quyết một việc cụ thể, chạy được — không gộp 5 việc khác nhau vào cùng 1 commit!

---

## 8. Quy Tắc Pull Request & Code Review

1. **Tên PR:** Đặt theo chuẩn tương tự commit message:  
   `feat(module3): Request workflow service - CRUD va state machine`
2. **Nội dung mô tả PR cần có:**
   - **Đã làm gì:** Các gạch đầu dòng tóm tắt tính năng mới / lỗi đã sửa.
   - **Cách kiểm thử:** Lệnh chạy test hoặc hướng dẫn gọi thử API qua Postman / curl.
3. **Kích thước PR:** Không nên vượt quá ~400 dòng thay đổi để người khác dễ review.
4. **Tiêu chí Review:**
   - Code có theo đúng mô hình **Controller mỏng - Service dày** không?
   - API trả về có dùng đúng `ApiResponse::success()` / `ApiResponse::error()` không?
   - Đã chạy `./vendor/bin/pint` và `php artisan test` chưa?
