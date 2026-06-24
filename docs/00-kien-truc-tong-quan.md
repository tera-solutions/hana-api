# Kiến trúc Tổng quan - Hana API

## 1. Công nghệ sử dụng

| Thành phần | Công nghệ |
|------------|-----------|
| Backend | PHP / Laravel |
| Database | MySQL (MariaDB 10.4) |
| Authentication | OAuth2 — Laravel Passport |
| API Format | RESTful JSON |
| File Storage | Laravel Media Library (`media` table) |
| Queue | Laravel Jobs (`jobs` table) |
| Cache | Laravel Cache (`cache`, `cache_locks` table) |

---

## 2. Kiến trúc Module

Backend được tổ chức theo kiến trúc **module hóa**. Mỗi module nằm trong `lib/app/Modules/` và hoạt động độc lập.

```
lib/app/Modules/
├── System/        # Hệ thống: Business, Branch, User, ActivityLog
├── CRM/           # Khách hàng: Lead, Parent, ParentStudent
├── Education/     # Giáo dục: Course, Class, Student, Lesson, Assignment, Exam...
├── HR/            # Nhân sự: Teacher, Certificate
└── Finance/       # Tài chính: Invoice, Payment, Debt, Promotion, Wallet
```

### Cấu trúc bên trong mỗi module

```
Module/
├── Actions/        # Business logic (mỗi action = 1 use case)
├── Enums/          # Hằng số, trạng thái (PHP Enum)
├── Events/         # Domain events
├── Http/
│   ├── Controllers/  # Nhận request, gọi Action
│   ├── Requests/     # Validation rules
│   └── Resources/    # Format response JSON
├── Models/         # Eloquent Model
├── Router/
│   └── api.php     # Định nghĩa routes
└── Services/       # Logic phức tạp, tái sử dụng
```

**Luồng xử lý request:**
```
Request → Controller → Action → Service → Model → Response Resource
```

---

## 3. API Routes

### Base URL & Versioning
```
{{baseUrl}}/api/v1/{module}/{resource}
```

### Prefix theo module

| Module | Prefix URL |
|--------|-----------|
| System | `/api/v1/sys/` |
| CRM | `/api/v1/crm/` |
| Education | `/api/v1/edu/` |
| HR | `/api/v1/hr/` |
| Finance | `/api/v1/fin/` |

Route file tổng hợp của mỗi module (`routes.php`) tự động load tất cả `Router/api.php` trong các sub-module — không cần đăng ký thủ công khi thêm module mới.

---

## 4. Xác thực (Authentication)

Hệ thống dùng **OAuth2 — Laravel Passport**.

### Luồng đăng nhập

```
1. Client gọi Device Init  → nhận {{deviceCode}}
2. Client gọi Login        → nhận {{token}} (access token)
3. Mọi request sau đó đính kèm: Authorization: Bearer {{token}}
4. Middleware auth.tera kiểm tra token và quyền (permission)
```

### Phân quyền

Mỗi route yêu cầu một **permission** cụ thể, ví dụ:
- `permission:assignment.list` — xem danh sách bài tập
- `permission:class.create` — tạo lớp học
- `permission:fin_invoice.approve` — duyệt hóa đơn

Quyền được gán qua `sys_roles` và `sys_permissions`.

---

## 5. Database — Quy ước đặt tên bảng

| Prefix | Module | Ví dụ |
|--------|--------|-------|
| `sys_` | System | `sys_business`, `sys_branches`, `sys_settings` |
| `crm_` | CRM | `crm_leads`, `crm_parents` |
| `edu_` | Education | `edu_students`, `edu_classes`, `edu_lessons` |
| `hr_` | HR | `hr_teachers`, `hr_payrolls`, `hr_timesheets` |
| `fin_` | Finance | `fin_invoices`, `fin_payments`, `fin_wallets` |

### Các cột chuẩn (hầu hết bảng đều có)

| Cột | Mô tả |
|-----|-------|
| `id` | Primary key (bigint unsigned) |
| `business_id` | Đơn vị kinh doanh (multi-tenant) |
| `branch_id` | Chi nhánh |
| `status` | Trạng thái (enum dạng string) |
| `created_by`, `updated_by`, `deleted_by` | Audit trail |
| `created_at`, `updated_at`, `deleted_at` | Timestamps + soft delete |

---

## 6. Danh sách đầy đủ bảng Database

### System
`sys_business` · `sys_branches` · `sys_settings` · `sys_roles` · `sys_permissions` · `sys_activity_logs` · `sys_reference_counts` · `users` · `oauth_*`

### CRM
`crm_leads` · `crm_lead_guardians` · `crm_lead_students` · `crm_lead_histories` · `crm_lead_tags` · `crm_lead_courses` · `crm_parents` · `crm_parent_student` · `crm_parent_histories` · `crm_parent_feedbacks` · `crm_placement_tests` · `crm_referrals` · `crm_reward_transactions` · `crm_tags` · `crm_submissions` · `crm_assignments`

### Education
`edu_students` · `edu_student_profiles` · `edu_student_histories` · `edu_student_levels` · `edu_student_level_histories` · `edu_student_level_assessments` · `edu_courses` · `edu_course_curriculums` · `edu_course_histories` · `edu_course_material` · `edu_classes` · `edu_class_students` · `edu_class_teacher` · `edu_class_schedules` · `edu_class_curriculums` · `edu_sessions` · `edu_session_feedbacks` · `edu_session_tags` · `edu_lessons` · `edu_lesson_histories` · `edu_lesson_plans` · `edu_lesson_plan_lessons` · `edu_lesson_plan_materials` · `edu_lesson_plan_versions` · `edu_attendances` · `edu_assignments` · `edu_assignment_targets` · `edu_assignment_submissions` · `edu_assignment_submission_files` · `edu_enrollments` · `edu_enrollment_histories` · `edu_enrollment_suspensions` · `edu_enrollment_transfers` · `edu_exams` · `edu_exam_sessions` · `edu_exam_registrations` · `edu_exam_results` · `edu_exam_questions` · `edu_exam_question` · `edu_questions` · `edu_question_answers` · `edu_question_categories` · `edu_question_tags` · `edu_question_tag_mappings` · `edu_question_versions` · `edu_question_statistics` · `edu_materials` · `edu_material_categories` · `edu_material_mappings` · `edu_material_versions` · `edu_rooms` · `edu_room_histories` · `edu_levels` · `edu_programs` · `edu_grades`

### HR
`hr_teachers` · `hr_teacher_profiles` · `hr_teacher_certificates` · `hr_teacher_skills` · `hr_teacher_histories` · `hr_teaching_sessions` · `hr_teaching_schedules` · `hr_teaching_hours` · `hr_timesheets` · `hr_payrolls` · `hr_contracts` · `hr_attendances` · `hr_attendance_details` · `hr_reviews` · `hr_kpis` · `hr_disciplinary_actions` · `hr_shift_swaps` · `hr_student_evaluations`

### Finance
`fin_accounts` · `fin_bank_accounts` · `fin_invoices` · `fin_invoice_items` · `fin_invoice_histories` · `fin_payments` · `fin_payment_allocations` · `fin_payment_histories` · `fin_payment_logs` · `fin_debts` · `fin_debt_adjustments` · `fin_promotions` · `fin_promotion_rules` · `fin_promotion_rewards` · `fin_promotion_usages` · `fin_vouchers` · `fin_referrals` · `fin_wallets` · `fin_wallet_transactions` · `fin_refunds`

---

## 7. Sơ đồ quan hệ chính

```
sys_business
    └── sys_branches
            └── crm_leads ──────────────────────┐
            └── crm_parents ────────────────┐    │
            └── edu_students ──────────────┤    │
                    └── edu_enrollments     │    │
                    └── edu_class_students  │    │
                    └── edu_attendances     │    │
                    └── edu_assignment_submissions
            └── edu_classes
                    └── edu_sessions ── edu_lessons
                    └── edu_assignments
            └── hr_teachers
                    └── hr_teacher_certificates
                    └── hr_timesheets ── hr_payrolls
            └── fin_invoices ── fin_payments
            └── fin_wallets ── fin_wallet_transactions
```
