# Module Education

Module lõi của hệ thống — quản lý toàn bộ hoạt động học tập: khóa học, lớp học, học viên, bài học, bài tập, kiểm tra, tài liệu và phòng học.

**API Prefix:** `/api/v1/edu/`  
**Code:** `lib/app/Modules/Education/`

---

## Sơ đồ quan hệ chính

```
Course (Khóa học)
    └── ClassRoom (Lớp học)
            ├── ClassSchedule (Lịch học)
            ├── ClassSession (Buổi học / Session)
            │       └── Lesson (Bài học)
            │               └── Attendance (Điểm danh)
            ├── Enrollment (Ghi danh học viên)
            └── Assignment (Bài tập)
                    └── Submission (Bài nộp của học viên)

Student (Học viên)
    └── StudentLevel (Trình độ học viên)

LessonPlan (Giáo án)
    ├── LessonPlanLesson (Bài học trong giáo án)
    ├── LessonPlanMaterial (Tài liệu trong giáo án)
    └── LessonPlanVersion (Phiên bản giáo án)

Exam (Bài kiểm tra)
    ├── ExamSession (Ca thi)
    ├── ExamRegistration (Đăng ký thi)
    └── ExamResult (Kết quả thi)

Question (Câu hỏi — Ngân hàng câu hỏi)
    ├── QuestionAnswer (Đáp án)
    ├── QuestionVersion (Phiên bản câu hỏi)
    └── QuestionCategory / QuestionTag

Material (Tài liệu học)
    └── MaterialCategory / MaterialVersion
```

---

## Sub-module: Course (Khóa học)

### Mục đích
Định nghĩa khóa học — bao gồm chương trình học, cấu trúc bài học và tài liệu kèm theo.

### Bảng dữ liệu

**`edu_courses`** — Thông tin khóa học

| Cột | Mô tả |
|-----|-------|
| `code`, `name` | Mã và tên khóa học |
| `level_id` | Trình độ yêu cầu |
| `description` | Mô tả khóa học |
| `status` | `active` / `inactive` |

**`edu_course_curriculums`** — Chương trình học (danh sách chủ đề / buổi học)

**`edu_course_material`** — Tài liệu đính kèm khóa học

**`edu_course_histories`** — Lịch sử thay đổi

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/edu/course/list` | Danh sách khóa học |
| GET | `/edu/course/detail/{id}` | Chi tiết khóa học |
| POST | `/edu/course/create` | Tạo khóa học |
| PUT | `/edu/course/update/{id}` | Cập nhật khóa học |

---

## Sub-module: ClassRoom (Lớp học)

### Mục đích
Lớp học là đơn vị vận hành chính — gắn với một khóa học, có lịch học cố định, danh sách giáo viên và học viên.

### Bảng dữ liệu

**`edu_classes`** — Thông tin lớp học

| Cột | Mô tả |
|-----|-------|
| `code`, `name` | Mã và tên lớp |
| `course_id` | Khóa học |
| `room_id` | Phòng học mặc định |
| `status` | `opening`, `suspended`, `closed` |
| `start_date`, `end_date` | Thời gian khai giảng / kết thúc |

**`edu_class_students`** — Học viên trong lớp (gồm `enrolled_at`, `status`)

**`edu_class_teacher`** — Giáo viên phụ trách lớp

**`edu_class_schedules`** — Lịch học cố định (`weekday`, `start_time`, `end_time`)

**`edu_class_curriculums`** — Chương trình học của lớp (copy từ `edu_course_curriculums`)

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/edu/class-room/list` | `class.list` | Danh sách lớp |
| GET | `/edu/class-room/detail/{id}` | `class.view` | Chi tiết lớp |
| POST | `/edu/class-room/create` | `class.create` | Tạo lớp |
| PUT | `/edu/class-room/update/{id}` | `class.update` | Cập nhật lớp |
| POST | `/edu/class-room/suspend/{id}` | `class.suspend` | Tạm ngừng lớp |
| POST | `/edu/class-room/restore/{id}` | `class.restore` | Khôi phục lớp |

---

## Sub-module: Student (Học viên)

### Mục đích
Quản lý hồ sơ học viên, trình độ học tập và lịch sử.

### Bảng dữ liệu

**`edu_students`** — Thông tin học viên

| Cột | Mô tả |
|-----|-------|
| `code` | Mã học viên (unique) |
| `user_id` | Tài khoản đăng nhập |
| `name`, `gender`, `dob` | Thông tin cá nhân |
| `phone`, `email` | Liên hệ |
| `status` | `active`, `suspended`, `dropped` |

**`edu_student_profiles`** — Thông tin mở rộng (địa chỉ, trường học, ghi chú...)

**`edu_student_levels`** — Trình độ hiện tại của học viên

**`edu_student_level_assessments`** — Đánh giá trình độ

**`edu_student_level_histories`** — Lịch sử thay đổi trình độ

**`edu_student_histories`** — Lịch sử thay đổi trạng thái

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/edu/student/list` | Danh sách học viên |
| GET | `/edu/student/detail/{id}` | Chi tiết học viên |
| POST | `/edu/student/create` | Tạo học viên |
| PUT | `/edu/student/update/{id}` | Cập nhật học viên |
| POST | `/edu/student/suspend/{id}` | Tạm ngừng |
| POST | `/edu/student/restore/{id}` | Khôi phục |

---

## Sub-module: Enrollment (Ghi danh)

### Mục đích
Quản lý việc học viên ghi danh vào lớp học — bao gồm lịch sử, tạm ngừng, và chuyển lớp.

### Bảng dữ liệu

**`edu_enrollments`**

| Cột | Mô tả |
|-----|-------|
| `student_id` | FK → edu_students |
| `class_id` | FK → edu_classes |
| `enrolled_at` | Ngày ghi danh |
| `status` | `active`, `suspended`, `transferred`, `completed` |

**`edu_enrollment_histories`** — Lịch sử thay đổi trạng thái ghi danh

**`edu_enrollment_suspensions`** — Chi tiết tạm ngừng (lý do, thời gian)

**`edu_enrollment_transfers`** — Chi tiết chuyển lớp (from/to class)

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| POST | `/edu/enrollment/create` | Ghi danh học viên vào lớp |
| POST | `/edu/enrollment/suspend/{id}` | Tạm ngừng ghi danh |
| POST | `/edu/enrollment/transfer/{id}` | Chuyển lớp |
| POST | `/edu/enrollment/restore/{id}` | Khôi phục ghi danh |

---

## Sub-module: Lesson (Bài học / Session)

### Mục đích
Mỗi buổi học thực tế được gọi là **Lesson** (hoặc Session). Giáo viên bắt đầu và kết thúc buổi học, ghi chú nội dung đã dạy.

### Bảng dữ liệu

**`edu_lessons`**

| Cột | Mô tả |
|-----|-------|
| `class_id` | FK → edu_classes |
| `session_id` | FK → edu_sessions |
| `teacher_id` | Giáo viên dạy |
| `scheduled_date` | Ngày học theo lịch |
| `actual_date` | Ngày học thực tế |
| `status` | `scheduled`, `started`, `completed`, `cancelled` |
| `note` | Ghi chú của giáo viên |

**`edu_sessions`** — Buổi học (session) trong hệ thống

**`edu_lesson_histories`** — Lịch sử thay đổi

**`edu_session_feedbacks`** — Phản hồi về buổi học

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/edu/lesson/list` | `lesson.list` | Danh sách bài học |
| GET | `/edu/lesson/detail/{id}` | `lesson.view` | Chi tiết bài học |
| POST | `/edu/lesson/generate/{classId}` | `lesson.update` | Tạo bài học từ lịch lớp |
| PUT | `/edu/lesson/update/{id}` | `lesson.update` | Cập nhật bài học |
| POST | `/edu/lesson/reschedule/{id}` | `lesson.reschedule` | Dời lịch |
| POST | `/edu/lesson/cancel/{id}` | `lesson.cancel` | Hủy bài học |
| POST | `/edu/lesson/lock/{id}` | `lesson.update` | Khóa bài học |
| POST | `/edu/lesson/unlock/{id}` | `lesson.unlock` | Mở khóa |

---

## Sub-module: Attendance (Điểm danh)

### Mục đích
Ghi nhận điểm danh học viên cho từng buổi học.

### Bảng dữ liệu: `edu_attendances`

| Cột | Mô tả |
|-----|-------|
| `session_id` | FK → edu_sessions |
| `student_id` | FK → edu_students |
| `status` | `present`, `absent`, `late`, `excused` |
| `checkin_time`, `checkout_time` | Giờ vào / ra |
| `note` | Ghi chú |

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/edu/attendance/list` | Danh sách điểm danh |
| POST | `/edu/attendance/save` | Lưu điểm danh |
| GET | `/edu/attendance/summary` | Tổng hợp điểm danh |

---

## Sub-module: Assignment (Bài tập)

### Mục đích
Giáo viên tạo bài tập và giao cho học viên (theo lớp, nhóm trình độ, hoặc từng học viên). Học viên nộp bài, giáo viên chấm điểm.

### Luồng trạng thái

```
draft → published → assigned (giao cho HS) → submitted (HS nộp) → graded (đã chấm)
```

### Bảng dữ liệu

**`edu_assignments`**

| Cột | Mô tả |
|-----|-------|
| `assignment_code`, `assignment_name` | Mã và tên bài tập |
| `assignment_type` | Loại bài tập |
| `course_id`, `class_room_id`, `lesson_id` | Gắn với khóa học / lớp / bài học |
| `instruction` | Hướng dẫn làm bài |
| `max_score` | Điểm tối đa |
| `due_date` | Hạn nộp |
| `allow_late_submission` | Cho phép nộp trễ |
| `allow_multiple_submission` | Cho phép nộp nhiều lần |
| `status` | `draft`, `published` |

**`edu_assignment_targets`** — Danh sách học viên được giao bài

**`edu_assignment_submissions`** — Bài nộp của học viên (`answer`, `score`, `comment`, `status`)

**`edu_assignment_submission_files`** — File đính kèm bài nộp

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/edu/assignment/list` | `assignment.list` | Danh sách bài tập |
| GET | `/edu/assignment/detail/{id}` | `assignment.view` | Chi tiết bài tập |
| POST | `/edu/assignment/create` | `assignment.create` | Tạo bài tập |
| PUT | `/edu/assignment/update/{id}` | `assignment.update` | Cập nhật |
| POST | `/edu/assignment/publish/{id}` | `assignment.update` | Công bố bài tập |
| DELETE | `/edu/assignment/delete/{id}` | `assignment.delete` | Xóa |
| POST | `/edu/assignment/assign/class/{id}` | `assignment.assign` | Giao theo lớp |
| POST | `/edu/assignment/assign/group/{id}` | `assignment.assign` | Giao theo nhóm trình độ |
| POST | `/edu/assignment/assign/student/{id}` | `assignment.assign` | Giao cho học viên |
| POST | `/edu/assignment/assign/lesson/{id}` | `assignment.assign` | Giao theo bài học |
| POST | `/edu/assignment/submit/{id}` | `assignment.update` | Học viên nộp bài |
| POST | `/edu/submission/grade/{id}` | `assignment.grade` | Chấm điểm bài nộp |
| POST | `/edu/submission/publish/{id}` | `assignment.result` | Công bố kết quả |

---

## Sub-module: Exam (Bài kiểm tra)

### Mục đích
Quản lý kỳ kiểm tra: tạo đề, ca thi, đăng ký thi, nhập điểm.

### Bảng dữ liệu

**`edu_exams`** — Kỳ kiểm tra

**`edu_exam_sessions`** — Ca thi (thời gian, phòng thi)

**`edu_exam_registrations`** — Đăng ký tham dự kỳ thi

**`edu_exam_results`** — Kết quả của học viên

**`edu_exam_questions`** — Câu hỏi trong đề thi

**`edu_grades`** — Bảng điểm

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/edu/exam/list` | Danh sách kỳ kiểm tra |
| GET | `/edu/exam/detail/{id}` | Chi tiết kỳ kiểm tra |
| POST | `/edu/exam/create` | Tạo kỳ kiểm tra |
| PUT | `/edu/exam/update/{id}` | Cập nhật |
| POST | `/edu/exam/save-score` | Nhập điểm |

---

## Sub-module: Question (Ngân hàng câu hỏi)

### Mục đích
Lưu trữ và quản lý ngân hàng câu hỏi dùng cho bài tập và kiểm tra.

### Bảng dữ liệu

**`edu_questions`**

| Cột | Mô tả |
|-----|-------|
| `content` | Nội dung câu hỏi |
| `type` | Loại: `single_choice`, `multiple_choice`, `essay` |
| `level` | Độ khó |
| `category_id` | Danh mục câu hỏi |
| `status` | `active` / `inactive` |

**`edu_question_answers`** — Đáp án (`content`, `is_correct`)

**`edu_question_categories`** — Danh mục câu hỏi

**`edu_question_tags`** và **`edu_question_tag_mappings`** — Tag câu hỏi

**`edu_question_versions`** — Phiên bản câu hỏi

**`edu_question_statistics`** — Thống kê sử dụng câu hỏi

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/edu/question/list` | `question.list` | Danh sách câu hỏi |
| GET | `/edu/question/detail/{id}` | `question.view` | Chi tiết câu hỏi |
| POST | `/edu/question/create` | `question.create` | Tạo câu hỏi |
| PUT | `/edu/question/update/{id}` | `question.update` | Cập nhật |
| DELETE | `/edu/question/delete/{id}` | `question.delete` | Xóa |

---

## Sub-module: LessonPlan (Giáo án)

### Mục đích
Giáo viên soạn giáo án cho từng buổi dạy — gồm nội dung bài học, tài liệu kèm theo và phân chia theo phiên bản.

### Bảng dữ liệu

**`edu_lesson_plans`** — Giáo án chính

**`edu_lesson_plan_lessons`** — Các bài học trong giáo án

**`edu_lesson_plan_materials`** — Tài liệu kèm theo

**`edu_lesson_plan_versions`** — Quản lý phiên bản giáo án

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/edu/lesson-plan/list` | Danh sách giáo án |
| POST | `/edu/lesson-plan/create` | Tạo giáo án |
| PUT | `/edu/lesson-plan/update/{id}` | Cập nhật giáo án |

---

## Sub-module: Material (Tài liệu học)

### Mục đích
Quản lý tài liệu học tập (PDF, video, audio, slide...) dùng trong khóa học và giáo án.

### Bảng dữ liệu

**`edu_materials`**

| Cột | Mô tả |
|-----|-------|
| `name`, `description` | Tên và mô tả |
| `type` | Loại tài liệu |
| `file_id` | FK → media |
| `category_id` | FK → edu_material_categories |

**`edu_material_categories`** — Danh mục tài liệu

**`edu_material_versions`** — Phiên bản tài liệu

**`edu_material_mappings`** — Gắn tài liệu với khóa học / bài học

### API Routes

| Method | Endpoint | Mô tả |
|--------|----------|-------|
| GET | `/edu/material/list` | Danh sách tài liệu |
| POST | `/edu/material/upload` | Upload tài liệu |
| GET | `/edu/material/download/{id}` | Tải tài liệu |
| DELETE | `/edu/material/delete/{id}` | Xóa tài liệu |

---

## Sub-module: Room (Phòng học)

### Mục đích
Quản lý phòng học và lịch sử sử dụng phòng.

### Bảng dữ liệu: `edu_rooms`

| Cột | Mô tả |
|-----|-------|
| `code`, `name` | Mã và tên phòng |
| `capacity` | Sức chứa |
| `branch_id` | Chi nhánh |
| `status` | `available`, `maintenance` |

**`edu_room_histories`** — Lịch sử sử dụng phòng

### API Routes

| Method | Endpoint | Permission | Mô tả |
|--------|----------|-----------|-------|
| GET | `/edu/room/list` | `room.list` | Danh sách phòng |
| GET | `/edu/room/detail/{id}` | `room.view` | Chi tiết phòng |
| POST | `/edu/room/create` | `room.create` | Tạo phòng |
| PUT | `/edu/room/update/{id}` | `room.update` | Cập nhật |

---

## Sub-module: Level (Trình độ)

### Mục đích
Định nghĩa các cấp độ trình độ học viên (Beginner, Elementary, Pre-Intermediate...).

### Bảng dữ liệu: `edu_levels`

| Cột | Mô tả |
|-----|-------|
| `code`, `name` | Mã và tên trình độ |
| `order` | Thứ tự sắp xếp |
| `description` | Mô tả |

**`edu_programs`** — Chương trình học theo trình độ
