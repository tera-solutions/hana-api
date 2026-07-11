<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds every feature's permission codes into sys_permissions in one pass.
 * Squashed from the previous one-seeder-per-feature layout — see git history
 * for the original per-feature files if you need their individual docblocks.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->system();
        $this->crm();
        $this->education();
        $this->hr();
        $this->finance();
    }

    /**
     * Idempotently upsert a feature's permissions into sys_permissions.
     *
     * @param  array<string, string>  $permissions  code => display name
     */
    private function seedPermissions(string $module, string $feature, array $permissions): void
    {
        foreach ($permissions as $code => $name) {
            DB::table('sys_permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'guard_name' => 'api',
                    'module' => $module,
                    'feature' => $feature,
                    'description' => $name,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    // ── System ───────────────────────────────────────────────────────────────

    private function system(): void
    {
        $this->seedPermissions('System', 'Business', [
            'business.list' => 'Xem danh sách',
            'business.view' => 'Xem chi tiết',
            'business.create' => 'Tạo mới',
            'business.update' => 'Cập nhật',
            'business.delete' => 'Xóa',
        ]);

        $this->seedPermissions('System', 'Branch', [
            'branch.list' => 'Xem danh sách',
            'branch.view' => 'Xem chi tiết',
            'branch.create' => 'Tạo mới',
            'branch.update' => 'Cập nhật',
            'branch.delete' => 'Xóa',
        ]);

        $this->seedPermissions('System', 'User', [
            'user.list' => 'Xem danh sách',
            'user.view' => 'Xem chi tiết',
            'user.create' => 'Tạo mới',
            'user.update' => 'Cập nhật',
            'user.delete' => 'Xóa',
            'user.activate' => 'Kích hoạt tài khoản',
            'user.deactivate' => 'Vô hiệu hóa tài khoản',
            'user.reset_password' => 'Đặt lại mật khẩu',
            'user.unlock' => 'Mở khóa tài khoản',
            'user.assign_role' => 'Gán vai trò',
        ]);

        $this->seedPermissions('System', 'ActivityLog', [
            'activity_log.list' => 'Xem danh sách nhật ký',
            'activity_log.view' => 'Xem chi tiết nhật ký',
            'activity_log.export' => 'Xuất nhật ký',
        ]);

        $this->seedPermissions('System', 'Task', [
            'task.list' => 'Xem danh sách công việc',
            'task.view' => 'Xem chi tiết công việc',
            'task.create' => 'Tạo công việc',
            'task.update' => 'Cập nhật công việc',
            'task.delete' => 'Xóa công việc',
        ]);
    }

    // ── CRM ──────────────────────────────────────────────────────────────────

    private function crm(): void
    {
        $this->seedPermissions('CRM', 'Lead', [
            'crm_lead.list' => 'Xem danh sách',
            'crm_lead.view' => 'Xem chi tiết',
            'crm_lead.create' => 'Tạo mới',
            'crm_lead.update' => 'Cập nhật',
            'crm_lead.suspend' => 'Ngừng khách hàng',
            'crm_lead.restore' => 'Khôi phục khách hàng',
        ]);

        $this->seedPermissions('CRM', 'Parent', [
            'parent.list' => 'Xem danh sách',
            'parent.view' => 'Xem chi tiết',
            'parent.create' => 'Tạo mới',
            'parent.update' => 'Cập nhật',
            'parent.suspend' => 'Tạm ngừng',
            'parent.restore' => 'Khôi phục',
        ]);

        $this->seedPermissions('CRM', 'ParentStudent', [
            'parent_student.list' => 'Xem danh sách',
            'parent_student.view' => 'Xem chi tiết',
            'parent_student.create' => 'Thêm quan hệ',
            'parent_student.update' => 'Cập nhật quan hệ',
            'parent_student.delete' => 'Xóa quan hệ',
        ]);
    }

    // ── Education ────────────────────────────────────────────────────────────

    private function education(): void
    {
        $this->seedPermissions('Education', 'Course', [
            'course.list' => 'Xem danh sách',
            'course.view' => 'Xem chi tiết',
            'course.create' => 'Tạo mới',
            'course.update' => 'Cập nhật',
            'course.suspend' => 'Ngừng hoạt động',
            'course.restore' => 'Khôi phục',
        ]);

        $this->seedPermissions('Education', 'CourseCurriculum', [
            'course_curriculum.list' => 'Xem danh sách',
            'course_curriculum.view' => 'Xem chi tiết',
            'course_curriculum.create' => 'Tạo mới',
            'course_curriculum.update' => 'Cập nhật',
            'course_curriculum.delete' => 'Xóa',
        ]);

        $this->seedPermissions('Education', 'Level', [
            'level.list' => 'Xem danh sách cấp độ',
            'level.view' => 'Xem chi tiết cấp độ',
            'level.create' => 'Tạo cấp độ',
            'level.update' => 'Cập nhật cấp độ',
            'level.suspend' => 'Ngừng sử dụng cấp độ',
            'level.restore' => 'Khôi phục cấp độ',
        ]);

        $this->seedPermissions('Education', 'StudentLevel', [
            'student_level.view' => 'Xem cấp độ học viên',
            'student_level.placement' => 'Đánh giá đầu vào',
            'student_level.promote' => 'Xét lên cấp',
            'student_level.adjust' => 'Điều chỉnh cấp độ',
            'student_level.history' => 'Xem lịch sử cấp độ',
        ]);

        $this->seedPermissions('Education', 'Student', [
            'student.list' => 'Xem danh sách',
            'student.view' => 'Xem chi tiết',
            'student.create' => 'Tạo mới',
            'student.update' => 'Cập nhật',
            'student.suspend' => 'Ngừng học',
            'student.restore' => 'Khôi phục',
            'student.delete' => 'Xóa',
        ]);

        $this->seedPermissions('Education', 'ClassRoom', [
            'class.list' => 'Xem danh sách',
            'class.view' => 'Xem chi tiết',
            'class.create' => 'Tạo mới',
            'class.update' => 'Cập nhật',
            'class.suspend' => 'Tạm ngừng',
            'class.restore' => 'Khôi phục',
        ]);

        $this->seedPermissions('Education', 'ClassSession', [
            'session.list' => 'Xem danh sách',
            'session.view' => 'Xem chi tiết',
            'session.create' => 'Tạo mới',
            'session.generate' => 'Sinh hàng loạt',
            'session.update' => 'Cập nhật',
            'session.start' => 'Bắt đầu buổi học',
            'session.end' => 'Kết thúc sớm',
            'session.cancel' => 'Hủy buổi học',
            'session.delete' => 'Xóa buổi học',
        ]);

        $this->seedPermissions('Education', 'Timetable', [
            'timetable.list' => 'Xem danh sách thời khóa biểu',
            'timetable.view' => 'Xem chi tiết / lịch thời khóa biểu',
            'timetable.create' => 'Tạo thời khóa biểu',
            'timetable.update' => 'Cập nhật thời khóa biểu',
            'timetable.delete' => 'Xóa thời khóa biểu',
        ]);

        $this->seedPermissions('Education', 'Attendance', [
            'attendance.list' => 'Xem danh sách chuyên cần',
            'attendance.view' => 'Xem chi tiết chuyên cần',
            'attendance.create' => 'Điểm danh học viên',
            'attendance.update' => 'Cập nhật điểm danh',
            'attendance.export' => 'Xuất báo cáo chuyên cần',
        ]);

        $this->seedPermissions('Education', 'SessionFeedback', [
            'session_feedback.list' => 'Xem ghi chú học viên theo buổi học',
            'session_feedback.create' => 'Ghi chú học viên theo buổi học',
        ]);

        $this->seedPermissions('Education', 'LessonPlan', [
            'lesson_plan.list' => 'Xem danh sách',
            'lesson_plan.view' => 'Xem chi tiết',
            'lesson_plan.create' => 'Tạo mới',
            'lesson_plan.update' => 'Cập nhật',
            'lesson_plan.delete' => 'Xóa',
            'lesson_plan.publish' => 'Xuất bản',
            'lesson_plan.clone' => 'Sao chép',
            'lesson_plan.version' => 'Quản lý phiên bản',
        ]);

        $this->seedPermissions('Education', 'Lesson', [
            'lesson.list' => 'Xem danh sách',
            'lesson.view' => 'Xem chi tiết',
            'lesson.update' => 'Cập nhật',
            'lesson.reschedule' => 'Đổi lịch',
            'lesson.cancel' => 'Hủy',
            'lesson.unlock' => 'Mở khóa',
        ]);

        $this->seedPermissions('Education', 'Room', [
            'room.list' => 'Xem danh sách',
            'room.view' => 'Xem chi tiết',
            'room.create' => 'Tạo mới',
            'room.update' => 'Cập nhật',
            'room.suspend' => 'Ngừng sử dụng',
            'room.restore' => 'Khôi phục',
        ]);

        $this->seedPermissions('Education', 'LeaveRequest', [
            'leave.list' => 'Xem danh sách',
            'leave.view' => 'Xem chi tiết',
            'leave.create' => 'Tạo đơn nghỉ',
            'leave.update' => 'Cập nhật đơn nghỉ',
            'leave.approve' => 'Duyệt đơn nghỉ',
            'leave.reject' => 'Từ chối đơn nghỉ',
            'leave.cancel' => 'Hủy đơn nghỉ',
            'leave.makeup' => 'Quản lý học bù',
        ]);

        $this->seedPermissions('Education', 'Material', [
            'material.list' => 'Xem danh sách',
            'material.view' => 'Xem chi tiết',
            'material.create' => 'Tải lên tài liệu',
            'material.update' => 'Cập nhật tài liệu',
            'material.delete' => 'Xóa tài liệu',
            'material.download' => 'Download tài liệu',
            'material.manage' => 'Quản lý thư viện',
        ]);

        $this->seedPermissions('Education', 'Assignment', [
            'assignment.list' => 'Xem danh sách',
            'assignment.view' => 'Xem bài tập',
            'assignment.create' => 'Tạo bài tập',
            'assignment.update' => 'Cập nhật bài tập',
            'assignment.delete' => 'Xóa bài tập',
            'assignment.assign' => 'Giao bài tập',
            'assignment.grade' => 'Chấm bài tập',
            'assignment.result' => 'Xem kết quả',
        ]);

        $this->seedPermissions('Education', 'Exam', [
            'exam.list' => 'Xem danh sách',
            'exam.view' => 'Xem bài kiểm tra',
            'exam.create' => 'Tạo bài kiểm tra',
            'exam.update' => 'Cập nhật bài kiểm tra',
            'exam.delete' => 'Xóa bài kiểm tra',
            'exam.schedule' => 'Tổ chức thi',
            'exam.grade' => 'Chấm thi',
            'exam.publish' => 'Công bố kết quả',
            'exam.promote' => 'Xét lên cấp',
        ]);

        $this->seedPermissions('Education', 'Question', [
            'question.list' => 'Xem danh sách',
            'question.view' => 'Xem câu hỏi',
            'question.create' => 'Tạo câu hỏi',
            'question.update' => 'Cập nhật câu hỏi',
            'question.delete' => 'Xóa câu hỏi',
            'question.import' => 'Import câu hỏi',
            'question.approve' => 'Duyệt câu hỏi',
            'question.generate_exam' => 'Sinh đề thi',
        ]);

        $this->seedPermissions('Education', 'Enrollment', [
            'enrollment.list' => 'Xem danh sách',
            'enrollment.view' => 'Xem chi tiết',
            'enrollment.create' => 'Tạo mới',
            'enrollment.update' => 'Cập nhật',
            'enrollment.suspend' => 'Bảo lưu',
            'enrollment.transfer' => 'Chuyển lớp',
            'enrollment.refund' => 'Hoàn phí',
            'enrollment.cancel' => 'Hủy ghi danh',
        ]);

        $this->seedPermissions('Education', 'Evaluation', [
            'evaluation.list' => 'Xem danh sách đánh giá',
            'evaluation.view' => 'Xem chi tiết đánh giá',
            'evaluation.create' => 'Tạo đánh giá',
            'evaluation.update' => 'Cập nhật đánh giá',
            'evaluation.delete' => 'Xóa đánh giá',
            'evaluation.approve' => 'Duyệt / khóa đánh giá',
        ]);
    }

    // ── HR ───────────────────────────────────────────────────────────────────

    private function hr(): void
    {
        $this->seedPermissions('HR', 'Teacher', [
            'teacher.list' => 'Xem danh sách',
            'teacher.view' => 'Xem chi tiết',
            'teacher.create' => 'Tạo mới',
            'teacher.update' => 'Cập nhật',
            'teacher.suspend' => 'Tạm ngừng',
            'teacher.restore' => 'Khôi phục',
            'teacher.resign' => 'Nghỉ việc',
        ]);

        $this->seedPermissions('HR', 'Achievement', [
            'achievement.view' => 'Xem thành tích',
            'teacher_review.create' => 'Gửi đánh giá giáo viên',
        ]);
    }

    // ── Finance ──────────────────────────────────────────────────────────────

    private function finance(): void
    {
        $this->seedPermissions('Finance', 'Account', [
            'fin_account.list' => 'Xem danh sách',
            'fin_account.view' => 'Xem chi tiết',
            'fin_account.create' => 'Tạo mới',
            'fin_account.update' => 'Cập nhật',
            'fin_account.suspend' => 'Ngừng quỹ',
            'fin_account.restore' => 'Khôi phục quỹ',
        ]);

        $this->seedPermissions('Finance', 'Invoice', [
            'fin_invoice.list' => 'Xem danh sách',
            'fin_invoice.view' => 'Xem chi tiết',
            'fin_invoice.create' => 'Tạo mới',
            'fin_invoice.update' => 'Cập nhật',
            'fin_invoice.approve' => 'Duyệt / Từ chối',
            'fin_invoice.cancel' => 'Hủy hóa đơn',
            'fin_invoice.refund' => 'Hoàn tiền',
            'fin_invoice.pay' => 'Ghi nhận thanh toán',
        ]);

        $this->seedPermissions('Finance', 'Payment', [
            'fin_payment.list' => 'Xem danh sách',
            'fin_payment.view' => 'Xem chi tiết',
            'fin_payment.create' => 'Tạo mới',
            'fin_payment.update' => 'Cập nhật',
            'fin_payment.confirm' => 'Xác nhận',
            'fin_payment.cancel' => 'Hủy giao dịch',
            'fin_payment.reverse' => 'Đảo giao dịch',
            'fin_payment.refund' => 'Hoàn tiền',
        ]);

        $this->seedPermissions('Finance', 'Debt', [
            'fin_debt.list' => 'Xem danh sách',
            'fin_debt.view' => 'Xem chi tiết',
            'fin_debt.adjust' => 'Điều chỉnh công nợ',
            'fin_debt.writeoff' => 'Xóa nợ',
            'fin_debt.collect' => 'Thu hồi công nợ',
            'fin_debt.reconcile' => 'Đối chiếu công nợ',
        ]);

        $this->seedPermissions('Finance', 'Promotion', [
            'promotion.list' => 'Xem danh sách',
            'promotion.view' => 'Xem chi tiết',
            'promotion.create' => 'Tạo khuyến mãi',
            'promotion.update' => 'Cập nhật khuyến mãi',
            'promotion.stop' => 'Dừng khuyến mãi',
            'promotion.approve' => 'Duyệt / kích hoạt khuyến mãi',
            'promotion.apply' => 'Áp dụng khuyến mãi',
        ]);

        $this->seedPermissions('Finance', 'Wallet', [
            'wallet.view' => 'Xem ví',
            'wallet.create' => 'Tạo ví',
            'wallet.lock' => 'Khóa ví',
            'wallet.deposit' => 'Nạp tiền',
            'wallet.payment' => 'Thanh toán bằng ví',
            'wallet.refund' => 'Hoàn tiền',
            'wallet.adjust' => 'Điều chỉnh ví',
            'wallet.transaction.view' => 'Xem giao dịch ví',
        ]);
    }
}
