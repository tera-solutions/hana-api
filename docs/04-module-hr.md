# Module HR (Human Resources)

Module quản lý nhân sự — tập trung vào giáo viên: hồ sơ, chứng chỉ, lịch dạy, bảng công và bảng lương.

**API Prefix:** `/api/v1/hr/`  
**Code:** `lib/app/Modules/HR/`

---

## Sub-module: Teacher (Giáo viên)

### Mục đích
Quản lý toàn bộ vòng đời nhân sự giáo viên từ khi tuyển dụng đến khi nghỉ việc. Bao gồm hồ sơ cá nhân, kỹ năng, chứng chỉ, lịch dạy, bảng công và tiền lương.

### Trạng thái giáo viên

```
active → suspended → restored → active
       ↓
     resigned (nghỉ việc)
```

### Bảng dữ liệu

**`hr_teachers`** — Thông tin cơ bản giáo viên

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `code` | varchar | Mã giáo viên (unique) |
| `user_id` | bigint | Tài khoản đăng nhập |
| `business_id`, `branch_id` | bigint | Đơn vị / Chi nhánh |
| `name` | varchar | Họ tên |
| `gender`, `dob` | — | Giới tính, ngày sinh |
| `phone`, `email` | — | Thông tin liên hệ |
| `type` | varchar | Loại: `full_time`, `part_time`, `freelance` |
| `status` | varchar | Trạng thái |

**`hr_teacher_profiles`** — Thông tin chi tiết (địa chỉ, học vấn, kinh nghiệm...)

**`hr_teacher_skills`** — Kỹ năng / môn dạy của giáo viên

**`hr_teacher_certificates`** — Chứng chỉ nghề nghiệp

| Cột | Mô tả |
|-----|-------|
| `teacher_id` | FK → hr_teachers |
| `name` | Tên chứng chỉ |
| `issuer` | Tổ chức cấp |
| `issued_date`, `expiry_date` | Ngày cấp / hết hạn |
| `file_id` | File scan chứng chỉ |

**`hr_teacher_histories`** — Lịch sử thay đổi trạng thái

### API Routes — Teacher

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/hr/teacher/list` | `teacher.list` | Danh sách giáo viên |
| GET | `/hr/teacher/detail/{id}` | `teacher.view` | Chi tiết giáo viên |
| POST | `/hr/teacher/create` | `teacher.create` | Tạo giáo viên |
| PUT | `/hr/teacher/update/{id}` | `teacher.update` | Cập nhật thông tin |
| POST | `/hr/teacher/suspend/{id}` | `teacher.suspend` | Tạm ngừng |
| POST | `/hr/teacher/restore/{id}` | `teacher.restore` | Khôi phục |
| POST | `/hr/teacher/resign/{id}` | `teacher.resign` | Nghỉ việc |

### API Routes — Chứng chỉ

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/hr/teacher/certificate/list/{teacherId}` | `teacher.view` | DS chứng chỉ |
| POST | `/hr/teacher/certificate/create/{teacherId}` | `teacher.update` | Thêm chứng chỉ |
| PUT | `/hr/teacher/certificate/update/{id}` | `teacher.update` | Sửa chứng chỉ |
| DELETE | `/hr/teacher/certificate/delete/{id}` | `teacher.update` | Xóa chứng chỉ |

---

## Bảng dữ liệu HR mở rộng

### Lịch dạy & Buổi dạy

**`hr_teaching_schedules`** — Lịch dạy đăng ký

| Cột | Mô tả |
|-----|-------|
| `teacher_id` | Giáo viên |
| `class_id` | Lớp học |
| `weekday` | Thứ trong tuần |
| `start_time`, `end_time` | Giờ dạy |

**`hr_teaching_sessions`** — Buổi dạy thực tế (gắn với `edu_lessons`)

**`hr_teaching_hours`** — Tổng hợp số giờ dạy theo tháng

### Bảng công (Timesheet)

**`hr_timesheets`** — Bảng công hàng tháng

| Cột | Mô tả |
|-----|-------|
| `teacher_id` | Giáo viên |
| `month`, `year` | Tháng / Năm |
| `total_sessions` | Tổng số buổi dạy |
| `total_hours` | Tổng giờ dạy |
| `status` | `draft`, `confirmed`, `approved` |

**`hr_attendances`** và **`hr_attendance_details`** — Chấm công chi tiết từng buổi

### Bảng lương (Payroll)

**`hr_payrolls`** — Bảng lương hàng tháng

| Cột | Mô tả |
|-----|-------|
| `teacher_id` | Giáo viên |
| `month`, `year` | Tháng / Năm |
| `base_salary` | Lương cơ bản |
| `teaching_pay` | Tiền công dạy |
| `bonus` | Thưởng |
| `deduction` | Khấu trừ |
| `total` | Thực lĩnh |
| `status` | `draft`, `approved`, `paid` |

### Hợp đồng & Đánh giá

**`hr_contracts`** — Hợp đồng lao động

**`hr_reviews`** — Đánh giá định kỳ (KPI)

**`hr_kpis`** — Chỉ tiêu KPI

**`hr_disciplinary_actions`** — Kỷ luật

**`hr_shift_swaps`** — Đổi ca dạy

**`hr_student_evaluations`** — Nhận xét học viên của giáo viên

---

## Luồng nghiệp vụ tiêu biểu

### Tính lương giáo viên hàng tháng

```
1. Hệ thống tổng hợp hr_teaching_sessions → hr_teaching_hours
2. Admin xác nhận bảng công → hr_timesheets (status: confirmed)
3. Admin tạo bảng lương → hr_payrolls (status: draft)
4. Admin duyệt bảng lương → hr_payrolls (status: approved)
5. Giáo viên nhận lương → hr_payrolls (status: paid)
                        → fin_wallet_transactions (cộng tiền vào ví)
```

### Nhận xét học viên

```
Giáo viên nhập nhận xét → hr_student_evaluations
    └── Phụ huynh xem qua app → crm_parent_feedbacks (phản hồi)
```
