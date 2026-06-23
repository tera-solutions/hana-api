<?php

namespace App\Modules\Education\LeaveRequest\Http\Requests;

use App\Modules\Education\LeaveRequest\Enums\LeaveReasonType;
use App\Modules\Education\LeaveRequest\Enums\LeaveRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', Rule::in(LeaveRequestType::values())],
            'requester_id' => ['required', 'integer'],
            'class_room_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:edu_lessons,id'],
            'leave_date' => ['required', 'date'],
            'reason_type' => ['required', Rule::in(LeaveReasonType::values())],
            'reason' => ['nullable', 'string'],
            'attachment_file_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'Loại đơn là bắt buộc.',
            'request_type.in' => 'Loại đơn không hợp lệ.',
            'requester_id.required' => 'Người gửi đơn là bắt buộc.',
            'leave_date.required' => 'Ngày nghỉ là bắt buộc.',
            'leave_date.date' => 'Ngày nghỉ không hợp lệ.',
            'reason_type.required' => 'Loại lý do là bắt buộc.',
            'reason_type.in' => 'Loại lý do không hợp lệ.',
            'lesson_id.exists' => 'Buổi học không tồn tại.',
            'class_room_id.exists' => 'Lớp học không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'request_type' => ['description' => 'Loại đơn: student_leave | teacher_leave.', 'example' => 'student_leave'],
            'requester_id' => ['description' => 'ID học viên (student_leave) hoặc giáo viên (teacher_leave).', 'example' => 1],
            'class_room_id' => ['description' => 'Lớp học liên quan (tùy chọn, tự suy ra từ buổi học).', 'example' => 1],
            'lesson_id' => ['description' => 'Buổi học xin nghỉ.', 'example' => 1],
            'leave_date' => ['description' => 'Ngày nghỉ (Y-m-d).', 'example' => '2026-06-25'],
            'reason_type' => ['description' => 'Loại lý do: sick | family | school_activity | vacation | personal | other.', 'example' => 'sick'],
            'reason' => ['description' => 'Mô tả lý do (tùy chọn).', 'example' => 'Sốt cao'],
            'attachment_file_id' => ['description' => 'ID file đính kèm (tùy chọn).', 'example' => 10],
        ];
    }
}
