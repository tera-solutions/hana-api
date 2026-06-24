# Module System

Module quản lý nền tảng hệ thống: đơn vị kinh doanh, chi nhánh, người dùng, phân quyền và nhật ký hoạt động.

**API Prefix:** `/api/v1/sys/`  
**Code:** `lib/app/Modules/System/`

---

## Sub-module: Business (Đơn vị kinh doanh)

### Mục đích
Quản lý đơn vị kinh doanh (tenant) ở cấp cao nhất. Mỗi business có thể có nhiều chi nhánh.

### Bảng dữ liệu: `sys_business`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | Primary key |
| `name` | varchar | Tên đơn vị |
| `status` | varchar | Trạng thái: `active`, `inactive` |
| `created_at`, `updated_at` | timestamp | Timestamps |

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/sys/business/list` | `business.list` | Danh sách business |
| GET | `/sys/business/detail/{id}` | `business.view` | Chi tiết business |
| POST | `/sys/business/create` | `business.create` | Tạo business |
| PUT | `/sys/business/update/{id}` | `business.update` | Cập nhật business |
| DELETE | `/sys/business/delete/{id}` | `business.delete` | Xóa business |

---

## Sub-module: Branch (Chi nhánh)

### Mục đích
Mỗi Business có thể có nhiều chi nhánh. Hầu hết dữ liệu (leads, students, teachers, classes...) đều gắn với `branch_id`.

### Bảng dữ liệu: `sys_branches`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | Primary key |
| `business_id` | bigint | FK → sys_business |
| `name` | varchar | Tên chi nhánh |
| `address` | text | Địa chỉ |
| `status` | varchar | Trạng thái |

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/sys/branch/list` | `branch.list` | Danh sách chi nhánh |
| GET | `/sys/branch/detail/{id}` | `branch.view` | Chi tiết chi nhánh |
| POST | `/sys/branch/create` | `branch.create` | Tạo chi nhánh |
| PUT | `/sys/branch/update/{id}` | `branch.update` | Cập nhật chi nhánh |
| DELETE | `/sys/branch/delete/{id}` | `branch.delete` | Xóa chi nhánh |

---

## Sub-module: User (Người dùng)

### Mục đích
Quản lý tài khoản đăng nhập hệ thống. User có thể là admin, giáo viên, phụ huynh, học viên. Phân quyền thông qua Roles & Permissions.

### Bảng dữ liệu: `users`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | Primary key |
| `name` | varchar | Tên người dùng |
| `email` | varchar | Email (unique) |
| `password` | varchar | Mật khẩu (hashed) |
| `status` | varchar | `active`, `inactive`, `locked` |

### Bảng phân quyền
- `sys_roles` — Danh sách vai trò
- `sys_permissions` — Danh sách quyền
- `role_has_permissions` — Gán quyền cho vai trò

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/sys/user/list` | `user.list` | Danh sách user |
| GET | `/sys/user/detail/{id}` | `user.view` | Chi tiết user |
| POST | `/sys/user/create` | `user.create` | Tạo user |
| PUT | `/sys/user/update/{id}` | `user.update` | Cập nhật user |
| DELETE | `/sys/user/delete/{id}` | `user.delete` | Xóa user |
| POST | `/sys/user/activate/{id}` | `user.activate` | Kích hoạt |
| POST | `/sys/user/deactivate/{id}` | `user.deactivate` | Vô hiệu hóa |
| POST | `/sys/user/unlock/{id}` | `user.unlock` | Mở khóa tài khoản |
| POST | `/sys/user/reset-password/{id}` | `user.reset_password` | Đặt lại mật khẩu |

---

## Sub-module: ActivityLog (Nhật ký hoạt động)

### Mục đích
Ghi lại mọi thao tác quan trọng trong hệ thống (tạo, cập nhật, xóa, thay đổi trạng thái).

### Bảng dữ liệu: `sys_activity_logs`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | Primary key |
| `business_id` | bigint | FK → sys_business |
| `user_id` | bigint | Người thực hiện |
| `module` | varchar | Module liên quan |
| `action` | varchar | Hành động (CREATE, UPDATE, DELETE...) |
| `subject_id` | bigint | ID đối tượng bị tác động |
| `data` | json | Dữ liệu trước/sau thay đổi |
| `created_at` | timestamp | Thời điểm thực hiện |

### Cơ chế hoạt động
ActivityLog dùng trait `LogsActivity` — gắn vào Model là tự động ghi log. Được kích hoạt qua Event `ActivityLogged` → Listener `WriteActivityLog`.

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/sys/activity-log/list` | Xem nhật ký hoạt động |
