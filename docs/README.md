# Tài liệu Kiến trúc và Chức năng - Hana API

> Phiên bản: 2026 | Cập nhật: 25/06/2026

## Mục lục

| File | Nội dung |
|------|----------|
| [00-kien-truc-tong-quan.md](./00-kien-truc-tong-quan.md) | Kiến trúc hệ thống, công nghệ, luồng xác thực |
| [01-module-system.md](./01-module-system.md) | Module System: Business, Branch, User, ActivityLog |
| [02-module-crm.md](./02-module-crm.md) | Module CRM: Lead, Parent, ParentStudent |
| [03-module-education.md](./03-module-education.md) | Module Education: Course, Class, Student, Lesson, Assignment, Exam, v.v. |
| [04-module-hr.md](./04-module-hr.md) | Module HR: Teacher, Payroll, Timesheet |
| [05-module-finance.md](./05-module-finance.md) | Module Finance: Invoice, Payment, Debt, Promotion, Wallet |
| [06-teacher-app.md](./06-teacher-app.md) | Teacher App (Frontend): màn hình, component, tích hợp API |

## Tổng quan hệ thống

**Hana** là nền tảng quản lý trung tâm giáo dục (EduCenter Management) gồm:

- **Backend API**: Laravel (PHP) — kiến trúc module hóa
- **Database**: MySQL — prefix bảng theo module (`crm_`, `edu_`, `hr_`, `fin_`, `sys_`)
- **Auth**: OAuth2 (Laravel Passport) + middleware `auth.tera`
- **Frontend Teacher App**: ứng dụng dành cho giáo viên (desktop + mobile)

## Cấu trúc module

```
Hana API
├── System   → Business, Branch, User, ActivityLog
├── CRM      → Lead, Parent, ParentStudent
├── Education → Course, Class, Lesson, Student, Assignment, Exam, Room, v.v.
├── HR       → Teacher, Certificate, Payroll, Timesheet
└── Finance  → Invoice, Payment, Debt, Promotion, Wallet
```

## Quy ước API

- **Base URL**: `{{baseUrl}}/api/v1/`
- **Auth**: `Authorization: Bearer {{token}}`
- **Format**: JSON
- **Prefix theo module**:
  - System: `/sys/`
  - CRM: `/crm/`
  - Education: `/edu/`
  - HR: `/hr/`
  - Finance: `/fin/`
