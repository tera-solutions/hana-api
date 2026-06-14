<?php

namespace App\Modules\Education\ClassSession\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'session_date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'substitute_teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id', 'different:teacher_id'],
            'room_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:5000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:crm_tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.date_format' => 'Giờ bắt đầu không hợp lệ (HH:MM).',
            'end_time.date_format' => 'Giờ kết thúc không hợp lệ (HH:MM).',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'substitute_teacher_id.different' => 'Giáo viên dạy thay phải khác giáo viên chính.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên buổi học.', 'example' => 'Buổi 1 - Introduction'],
            'session_date' => ['description' => 'Ngày học (Y-m-d).', 'example' => '2026-07-02'],
            'start_time' => ['description' => 'Giờ bắt đầu (HH:MM).', 'example' => '19:00'],
            'end_time' => ['description' => 'Giờ kết thúc (HH:MM).', 'example' => '20:30'],
            'teacher_id' => ['description' => 'ID giáo viên giảng dạy.', 'example' => 2],
            'substitute_teacher_id' => ['description' => 'ID giáo viên dạy thay.', 'example' => 3],
            'room_id' => ['description' => 'ID phòng học.', 'example' => 4],
            'note' => ['description' => 'Ghi chú.'],
            'tag_ids' => ['description' => 'Danh sách ID thẻ (thay thế toàn bộ thẻ hiện có).', 'example' => [1, 2]],
        ];
    }
}
