<?php

namespace App\Modules\Education\LeaveRequest\Http\Requests;

use App\Modules\Education\LeaveRequest\Enums\LeaveReasonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_room_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'lesson_id' => ['nullable', 'integer', 'exists:edu_lessons,id'],
            'leave_date' => ['sometimes', 'date'],
            'reason_type' => ['sometimes', Rule::in(LeaveReasonType::values())],
            'reason' => ['nullable', 'string'],
            'attachment_file_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'leave_date.date' => 'Ngày nghỉ không hợp lệ.',
            'reason_type.in' => 'Loại lý do không hợp lệ.',
            'lesson_id.exists' => 'Buổi học không tồn tại.',
            'class_room_id.exists' => 'Lớp học không tồn tại.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'class_room_id' => ['description' => 'Lớp học liên quan.', 'example' => 1],
            'lesson_id' => ['description' => 'Buổi học xin nghỉ.', 'example' => 1],
            'leave_date' => ['description' => 'Ngày nghỉ (Y-m-d).', 'example' => '2026-06-25'],
            'reason_type' => ['description' => 'Loại lý do.', 'example' => 'family'],
            'reason' => ['description' => 'Mô tả lý do.', 'example' => 'Việc gia đình'],
            'attachment_file_id' => ['description' => 'ID file đính kèm.', 'example' => 10],
        ];
    }
}
