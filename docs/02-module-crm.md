# Module CRM

Module quản lý khách hàng tiềm năng (Lead), phụ huynh (Parent) và mối liên kết phụ huynh — học viên.

**API Prefix:** `/api/v1/crm/`  
**Code:** `lib/app/Modules/CRM/`

---

## Sub-module: Lead (Khách hàng tiềm năng)

### Mục đích
Quản lý vòng đời của khách hàng tiềm năng từ lúc tiếp cận đến khi ghi danh học. Mỗi Lead có thể có nhiều người giám hộ (Guardian) và được liên kết với học viên (Student).

### Trạng thái Lead

```
new → contacted → consulting → demo → enrolled / lost
                    ↓
                suspended (tạm ngừng)
```

### Bảng dữ liệu chính

**`crm_leads`**

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `id` | bigint | Primary key |
| `code` | varchar | Mã lead (unique) |
| `business_id`, `branch_id` | bigint | Đơn vị / Chi nhánh |
| `name` | varchar | Tên lead |
| `gender`, `dob` | varchar/date | Giới tính, ngày sinh |
| `phone`, `email` | varchar | Thông tin liên hệ |
| `source` | varchar | Nguồn tiếp cận |
| `owner_id` | bigint | Nhân viên phụ trách |
| `status` | varchar | Trạng thái hiện tại |
| `previous_status` | varchar | Trạng thái trước |
| `suspend_reason` | text | Lý do tạm ngừng |
| `note` | text | Ghi chú |

**`crm_lead_guardians`** — Người giám hộ của Lead

| Cột | Mô tả |
|-----|-------|
| `lead_id` | FK → crm_leads |
| `full_name` | Họ tên người giám hộ |
| `relationship` | Quan hệ (bố, mẹ, anh/chị...) |
| `phone`, `email` | Thông tin liên hệ |

**`crm_lead_students`** — Liên kết Lead với Học viên đã được tạo

**`crm_lead_histories`** — Lịch sử thay đổi trạng thái

**`crm_tags`** và **`crm_lead_tags`** — Gắn tag cho Lead

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/crm/lead/list` | `crm_lead.list` | Danh sách lead |
| GET | `/crm/lead/detail/{id}` | `crm_lead.view` | Chi tiết lead |
| POST | `/crm/lead/create` | `crm_lead.create` | Tạo lead mới |
| PUT | `/crm/lead/update/{id}` | `crm_lead.update` | Cập nhật lead |
| POST | `/crm/lead/suspend/{id}` | `crm_lead.suspend` | Tạm ngừng lead |
| POST | `/crm/lead/restore/{id}` | `crm_lead.restore` | Khôi phục lead |
| GET | `/crm/lead/{leadId}/guardian/list` | `crm_lead.view` | DS người giám hộ |
| POST | `/crm/lead/{leadId}/guardian/add` | `crm_lead.update` | Thêm người giám hộ |
| PUT | `/crm/lead/{leadId}/guardian/update/{id}` | `crm_lead.update` | Sửa người giám hộ |
| DELETE | `/crm/lead/{leadId}/guardian/delete/{id}` | `crm_lead.update` | Xóa người giám hộ |
| GET | `/crm/lead/{leadId}/student/list` | `crm_lead.view` | DS học viên liên kết |
| POST | `/crm/lead/{leadId}/student/add` | `crm_lead.update` | Thêm học viên |
| DELETE | `/crm/lead/{leadId}/student/delete/{id}` | `crm_lead.update` | Xóa liên kết học viên |

---

## Sub-module: Parent (Phụ huynh)

### Mục đích
Quản lý thông tin phụ huynh — những người có học viên đang học tại trung tâm. Phụ huynh có tài khoản riêng để theo dõi tiến độ học tập của con.

### Trạng thái: `active` / `suspended`

### Bảng dữ liệu: `crm_parents`

| Cột | Kiểu | Mô tả |
|-----|------|-------|
| `code` | varchar | Mã phụ huynh |
| `user_id` | bigint | Liên kết tài khoản đăng nhập |
| `name` | varchar | Họ tên |
| `gender`, `dob` | — | Giới tính, ngày sinh |
| `avatar` | varchar | Ảnh đại diện |
| `phone`, `email` | — | Liên hệ |
| `address`, `province`, `district` | — | Địa chỉ |
| `occupation`, `company` | — | Nghề nghiệp |

**`crm_parent_histories`** — Lịch sử thay đổi trạng thái

**`crm_parent_feedbacks`** — Phản hồi của phụ huynh về giáo viên (gồm `rating` và `content`)

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/crm/parent/list` | `crm_parent.list` | Danh sách phụ huynh |
| GET | `/crm/parent/detail/{id}` | `crm_parent.view` | Chi tiết phụ huynh |
| POST | `/crm/parent/create` | `crm_parent.create` | Tạo phụ huynh |
| PUT | `/crm/parent/update/{id}` | `crm_parent.update` | Cập nhật phụ huynh |
| POST | `/crm/parent/suspend/{id}` | `crm_parent.suspend` | Tạm ngừng |
| POST | `/crm/parent/restore/{id}` | `crm_parent.restore` | Khôi phục |

---

## Sub-module: ParentStudent (Liên kết Phụ huynh — Học viên)

### Mục đích
Quản lý mối quan hệ giữa phụ huynh và học viên, kèm các cờ phân loại vai trò.

### Bảng dữ liệu: `crm_parent_student`

| Cột | Mô tả |
|-----|-------|
| `parent_id` | FK → crm_parents |
| `student_id` | FK → edu_students |
| `relation` | Quan hệ (bố, mẹ, ông bà...) |
| `is_primary_contact` | Đầu mối liên hệ chính |
| `is_billing_contact` | Người chịu trách nhiệm thanh toán |
| `is_pickup_authorized` | Được phép đón học viên |
| `note` | Ghi chú thêm |

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/crm/parent-student/list` | Danh sách liên kết |
| GET | `/crm/parent-student/detail/{id}` | Chi tiết liên kết |
| POST | `/crm/parent-student/create` | Tạo liên kết |
| PUT | `/crm/parent-student/update/{id}` | Cập nhật liên kết |
| DELETE | `/crm/parent-student/delete/{id}` | Xóa liên kết |

---

## Các bảng phụ trợ CRM khác

| Bảng | Mô tả |
|------|-------|
| `crm_placement_tests` | Kết quả kiểm tra đầu vào của học viên |
| `crm_reward_transactions` | Lịch sử tích điểm thưởng của học viên |
| `crm_referrals` | Chương trình giới thiệu học viên mới |
| `crm_tags` | Tag dùng để phân loại Lead |
