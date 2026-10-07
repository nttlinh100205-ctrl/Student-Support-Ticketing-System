# Hướng Dẫn Quy Trình Làm Việc Cho Thành Viên Khi Sửa Code
> **Contribution Guide — Student Support Ticketing System**

Tài liệu này hướng dẫn chi tiết từng bước cho các thành viên trong nhóm mỗi khi phát triển tính năng mới, sửa lỗi hoặc chỉnh sửa mã nguồn trong dự án.

---

## ⚡ Tóm Tắt Quy Trình 6 Bước

```text
[1. Kéo code main mới] ──► [2. Tạo nhánh mới] ──► [3. Code trong module của mình]
                                                            │
[6. Nhóm Review & Merge] ◄── [5. Tạo Pull Request] ◄── [4. Test, Pint & Commit]
```

---

## Bước 1: Luôn Cập Nhật Code Mới Nhất Trước Khi Bắt Đầu

Trước khi bắt tay vào code bất kỳ tính năng nào, luôn chuyển về nhánh `main` và kéo code mới nhất của cả nhóm về:

```bash
git checkout main
git pull origin main
```

---

## Bước 2: Tạo Nhánh Mới Cho Tính Năng Của Bạn

Không code trực tiếp trên nhánh `main`. Hãy tạo một nhánh riêng biệt bắt nguồn từ `main`:

```bash
# Cú pháp đặt tên nhánh: feature/<tên-module>-<tên-ngắn-tính-năng>
# Ví dụ:
git checkout -b feature/module3-export-report
git checkout -b fix/module2-update-department
```

---

## Bước 3: Thực Hiện Viết Code (Quy Tắc Vàng)

### 1. Chỉ làm việc trong thư mục Module của bạn:
- Thành viên Module 1 chỉ sửa trong `module1-account-service/`
- Thành viên Module 2 chỉ sửa trong `module2-catalog-service/`
- Thành viên Module 3 chỉ sửa trong `module3-request-service/`
- Thành viên Module 5 chỉ sửa trong `report-service/`
- **Không tự ý sửa file ở thư mục gốc hoặc trong thư mục module của bạn khác** để tránh gây xung đột (conflict).

### 2. Tuân thủ 2 tài liệu chuẩn hóa:
- Đọc kỹ **[API_CONTRACT.md](API_CONTRACT.md)**:
  - Mọi API phải trả về qua `ApiResponse::success()` và `ApiResponse::error()`.
  - Mọi kiểm tra đăng nhập/quyền dùng header `X-User-Id`, `X-User-Role` hoặc JWT.
- Đọc kỹ **[CODING_CONVENTIONS.md](CODING_CONVENTIONS.md)**:
  - **Controller mỏng (~15 dòng):** Chỉ nhận dữ liệu và trả response.
  - **Service dày:** Toàn bộ tính toán, logic nghiệp vụ đưa xuống `app/Services/`.
  - **Validate bằng Form Request:** Tạo file trong `app/Http/Requests/`.
  - Tên class, hàm, biến viết bằng tiếng Anh chuẩn.

### 3. Nếu cần dữ liệu từ Module khác:
- Sử dụng **Mock Data** (dữ liệu mẫu JSON) nếu module đó chưa chạy thật.
- Gọi qua HTTP Client (`Http::get('http://localhost:8002/api/...')`) theo cổng service quy định.

---

## Bước 4: Kiểm Tra Trước Khi Commit (Bắt Buộc)

Di chuyển vào thư mục module của bạn và thực hiện 2 lệnh sau:

### 1. Tự động định dạng code bằng Laravel Pint:
```bash
cd module3-request-service
./vendor/bin/pint
```
*(Lệnh này sẽ tự động sửa khoảng trắng, thụt dòng, dấu ngoặc theo chuẩn PSR-12).*

### 2. Chạy bài kiểm thử tự động (Unit / Feature Test):
```bash
php artisan test
```
> **Yêu cầu:** Tất cả các bài test phải hiển thị màu xanh lá (`PASS`). Nếu có lỗi `FAIL`, hãy sửa code cho đến khi pass toàn bộ mới được commit.

---

## Bước 5: Commit và Push Lên GitHub

Quay về thư mục gốc của dự án:

```bash
cd ..
```

### 1. Chỉ stage các file trong module của bạn:
```bash
# Ví dụ bạn làm Module 3:
git add module3-request-service/
```

### 2. Viết commit message theo chuẩn Conventional Commits:
```bash
# Cú pháp: <loại>(<phạm vi>): <mô tả ngắn gọn bằng tiếng Việt>
git commit -m "feat(module3): bo sung API tim kiem ticket theo ma sinh vien"
```
*(Các loại phổ biến: `feat` = tính năng mới, `fix` = sửa lỗi, `refactor` = tối ưu code, `test` = thêm test).*

### 3. Đẩy nhánh lên GitHub:
```bash
git push origin feature/module3-export-report
```

---

## Bước 6: Mở Pull Request (PR) Trên GitHub & Review

1. Truy cập vào GitHub: [Student-Support-Ticketing-System](https://github.com/nttlinh100205-ctrl/Student-Support-Ticketing-System).
2. GitHub sẽ hiện thông báo màu vàng: bấm nút **Compare & pull request**.
3. Điền mô tả PR:
   - **Nội dung:** Tóm tắt 2-3 gạch đầu dòng những gì bạn đã làm.
   - **Cách test:** Hướng dẫn các bạn khác cách gọi thử API hoặc chạy lệnh test.
4. Báo cho đồng đội trong nhóm vào xem (Code Review).
5. Khi không có thắc mắc gì, bấm **Merge pull request** để đưa code vào `main`.

---

## Bước 7: Dọn Dẹp Sau Khi Merge Thành Công

Sau khi nhánh của bạn đã được merge vào `main` trên GitHub:

```bash
# 1. Chuyển về main
git checkout main

# 2. Kéo code main mới nhất về (đã có tính năng vừa merge)
git pull origin main

# 3. Xóa nhánh tính năng cũ ở local để giữ máy gọn gàng
git branch -d feature/module3-export-report
```

Chúc các bạn làm việc hiệu quả và cùng nhau hoàn thiện dự án một cách chuyên nghiệp nhất! 🚀
