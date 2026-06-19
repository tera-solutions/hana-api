<?php

namespace App\Modules\Education\Exam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Exam sitting input (spec §VIII). Schedule conflicts (BR001/BR002) are enforced by the service.
 */
class CreateExamSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'integer', 'exists:edu_exams,id'],
            'class_room_id' => ['nullable', 'integer', 'exists:edu_classes,id'],
            'room_id' => ['nullable', 'integer', 'exists:edu_rooms,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_id.required' => 'Bài kiểm tra là bắt buộc.',
            'exam_date.required' => 'Ngày thi là bắt buộc.',
            'start_time.required' => 'Giờ bắt đầu là bắt buộc.',
            'end_time.required' => 'Giờ kết thúc là bắt buộc.',
            'end_time.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'exam_id' => ['description' => 'Exam this sitting runs.', 'example' => 1],
            'class_room_id' => ['description' => 'Class to seat (optional).', 'example' => 1],
            'room_id' => ['description' => 'Physical room (optional).', 'example' => 1],
            'teacher_id' => ['description' => 'Invigilator (optional).', 'example' => 1],
            'exam_date' => ['description' => 'Exam date (Y-m-d).', 'example' => '2026-07-15'],
            'start_time' => ['description' => 'Start time (H:i).', 'example' => '09:00'],
            'end_time' => ['description' => 'End time (H:i), after start_time.', 'example' => '10:30'],
        ];
    }
}
