# Teacher App — Tài liệu Frontend

Ứng dụng dành cho giáo viên — có cả phiên bản **desktop** và **mobile**. Được phát triển theo 4 Sprint.

**Thiết kế UI:** `C:\Users\Admin\Downloads\Hana App\teacher\desktop\` và `\mobile\`

---

## Cấu trúc màn hình theo Sprint

### Sprint 2 — Chức năng cơ bản

| Task | Màn hình | API chính cần tích hợp |
|------|----------|------------------------|
| [030] | Đăng nhập | POST /auth/login, GET /auth/profile |
| [031] | Trang chủ (Dashboard) | GET /dashboard/summary, /schedule/summary, /homework/summary, /notification/summary |
| [032] | Thông báo | GET /notification/list, /notification/detail, POST /notification/mark-read |
| [033] | Lịch dạy | GET /hr/teaching-schedule/list, /detail |
| [034] | Lớp học | GET /edu/class-room/list (filter, search, pagination) |
| [035] | Chi tiết lớp học | GET /edu/class-room/detail, /edu/student/list, /edu/attendance/summary |
| [036] | Giáo án | GET/POST/PUT /edu/lesson-plan, POST /edu/material/upload |
| [037] | Bài học | POST /edu/lesson/start, /edu/lesson/end, POST /edu/lesson/save-note |
| [038] | Điểm danh | GET /edu/attendance/list, POST /edu/attendance/save |
| [039] | Học viên | GET /edu/student/list, /detail (pagination, filter) |
| [060] | Đăng ký (tài khoản) | POST /auth/register |

---

### Sprint 3 — Chức năng nâng cao

| Task | Màn hình | API chính cần tích hợp |
|------|----------|------------------------|
| [040] | Chi tiết học viên | GET /edu/student/detail, /edu/student/learning-progress, /edu/attendance/history |
| [041] | Nhận xét học viên | GET/POST/PUT /hr/student-evaluation |
| [042] | Bài tập | GET/POST/PUT /edu/assignment, POST /edu/material/upload |
| [043] | Chấm bài | GET /edu/submission/list, /detail, POST /edu/submission/grade |
| [044] | Bài kiểm tra | GET /edu/exam/list, /detail, POST /edu/exam/save-score |
| [045] | Chi tiết bài kiểm tra | GET /edu/exam/detail, /edu/exam/result-detail |
| [046] | Thành tích | GET /edu/achievement/summary, /detail |
| [047] | Bảng xếp hạng | GET /edu/ranking/list |
| [048] | Tin nhắn | GET /message/conversation/list, /history, POST /message/send |
| [049] | Hồ sơ cá nhân | GET/PUT /hr/teacher/profile, POST /auth/change-password |
| [061] | Phụ huynh | GET /crm/parent/list, /detail (pagination, search) |
| [062] | Chi tiết phụ huynh | GET /crm/parent/detail, /crm/parent-student/linked |
| [063] | Phòng học | GET /edu/room/list (pagination, filter) |
| [064] | Chi tiết phòng học | GET /edu/room/detail, /edu/room/schedule |
| [065] | Chi tiết khóa học | GET /edu/course/detail, /edu/course/curriculum |
| [066] | Ghi danh học viên | POST /edu/enrollment/create |
| [067] | Chuyển lớp | POST /edu/enrollment/transfer |

---

### Sprint 4 — Tài chính & Hoàn thiện

| Task | Màn hình | API chính cần tích hợp |
|------|----------|------------------------|
| [050] | Ví cá nhân | GET /fin/wallet/summary, /fin/wallet/transactions |
| [051] | Nạp tiền | POST /fin/wallet/deposit |
| [052] | Rút tiền | POST /fin/wallet/withdraw, GET /fin/wallet/withdrawal-history |
| [053] | Bảng công | GET /hr/timesheet, /hr/teaching-session/list |
| [054] | Bảng lương | GET /hr/payroll/list, /summary |
| [055] | Chi tiết bảng lương | GET /hr/payroll/detail (breakdown, bonus, deduction) |
| [056] | Gói đăng ký | GET /fin/subscription/list, /detail |
| [057] | Tài liệu | GET /edu/material/list, POST /upload, GET /download |
| [058] | Cài đặt | GET/PUT /sys/setting, /notification-setting |
| [068] | Đơn xin nghỉ | GET/POST /hr/leave-request, GET /status |
| [069] | Kiểm tra đầu vào | GET/POST /crm/placement-test, GET /result |
| [070] | Ngân hàng câu hỏi | GET/POST/PUT/DELETE /edu/question |
| [071] | Quản lý gói | GET /fin/package-management/list, /subscription, /status |
| [072] | Báo cáo | GET /report/list, /detail, GET /report/export |
| [073] | Hóa đơn | GET /fin/invoice/list, /detail, GET /fin/invoice/download |
| [059] | Hoàn thiện hệ thống | Chuẩn hóa toàn bộ |

---

## Quy ước Component

### Naming Convention

```
[Feature]Table     → Bảng danh sách dữ liệu
[Feature]Form      → Form tạo / chỉnh sửa
[Feature]Detail    → Hiển thị chi tiết
[Feature]Filter    → Bộ lọc
[Feature]Status    → Badge trạng thái
[Feature]Card      → Card thông tin tổng quan
Upload[Type]       → Component upload file
```

**Ví dụ:**
- `HomeworkTable`, `HomeworkForm`, `HomeworkDetail`
- `AttendanceTable`, `AttendanceStatus`, `QuickAttendance`, `BulkAttendance`
- `UploadMaterial`, `UploadAttachment`

---

## Quy ước phát triển

### Cấu trúc Task

Mỗi màn hình gồm các bước theo thứ tự:

1. **Xây dựng UI** — dựng giao diện từ file thiết kế
2. **Tách Components** — chia nhỏ thành các component tái sử dụng
3. **Tích hợp API** — kết nối với backend
4. **Validation** — kiểm tra dữ liệu đầu vào (nếu có form)
5. **State Management** — quản lý state (loading, empty, error)
6. **Testing** — kiểm thử chức năng

### Label Trello

```
[Sprint ID], Teacher, Frontend
```

---

## Checklist Sprint 4 — Hoàn thiện (Task [059])

Trước khi release, cần đảm bảo:

- [ ] Chuẩn hóa Service Layer (tất cả API calls qua service)
- [ ] Chuẩn hóa API Layer (base URL, headers, interceptors)
- [ ] Chuẩn hóa Query Adapter (params, pagination, filter)
- [ ] Chuẩn hóa Form Components (validation, error messages)
- [ ] Chuẩn hóa Table Components (sort, filter, pagination)
- [ ] Bổ sung Loading State cho tất cả màn hình
- [ ] Bổ sung Empty State khi không có dữ liệu
- [ ] Bổ sung Error State khi API lỗi
- [ ] Bổ sung Permission Control (ẩn/hiện theo quyền)
- [ ] Bổ sung Route Guard (kiểm tra đăng nhập)
- [ ] Kiểm thử Responsive (desktop + mobile)
- [ ] Thực hiện UAT với người dùng thực
- [ ] Sửa lỗi sau UAT
- [ ] Tối ưu hiệu năng (lazy loading, memoization)
- [ ] Chuẩn bị Release Build

---

## Liên kết tài liệu

- **Thiết kế desktop:** `C:\Users\Admin\Downloads\Hana App\teacher\desktop\`
- **Thiết kế mobile:** `C:\Users\Admin\Downloads\Hana App\teacher\mobile\`
- **Task Trello:** Xem file `task.md` trong `agents/claude/teacher/`
- **API Postman:** https://documenter.getpostman.com/view/28246687/2sBXwwn83a
