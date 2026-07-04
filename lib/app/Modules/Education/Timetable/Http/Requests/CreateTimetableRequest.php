<?php

namespace App\Modules\Education\Timetable\Http\Requests;

use App\Modules\Education\Timetable\Enums\SchedulePattern;
use App\Modules\Education\Timetable\Enums\TimetableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:edu_courses,id'],
            'class_room_id' => ['required', 'integer', 'exists:edu_classes,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'room_id' => ['nullable', 'integer', 'exists:edu_rooms,id'],

            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            'schedule_pattern' => ['nullable', Rule::in(SchedulePattern::values())],
            'status' => ['nullable', Rule::in(TimetableStatus::values())],

            'rules' => ['nullable', 'required_without:dates', 'array'],
            'rules.*.day_of_week' => ['required_with:rules', 'integer', 'between:1,7'],
            'rules.*.start_time' => ['required_with:rules', 'date_format:H:i'],
            'rules.*.end_time' => ['required_with:rules', 'date_format:H:i', 'after:rules.*.start_time'],

            'dates' => ['nullable', 'required_without:rules', 'array'],
            'dates.*.date' => ['required_with:dates', 'date'],
            'dates.*.start_time' => ['required_with:dates', 'date_format:H:i'],
            'dates.*.end_time' => ['required_with:dates', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên thời khóa biểu là bắt buộc.',
            'class_room_id.required' => 'Lớp học là bắt buộc.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'end_date.required' => 'Ngày kết thúc là bắt buộc.',
            'end_date.after_or_equal' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'rules.required_without' => 'Cần cấu hình lịch theo tuần hoặc theo ngày cụ thể.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên thời khóa biểu.', 'example' => 'TKB Starter A - HK1'],
            'course_id' => ['description' => 'Khóa học.', 'example' => 1],
            'class_room_id' => ['description' => 'Lớp học (edu_classes).', 'example' => 1],
            'teacher_id' => ['description' => 'Giáo viên phụ trách.', 'example' => 1],
            'room_id' => ['description' => 'Phòng học.', 'example' => 1],
            'start_date' => ['description' => 'Ngày bắt đầu.', 'example' => '2026-07-01'],
            'end_date' => ['description' => 'Ngày kết thúc.', 'example' => '2026-08-31'],
            'schedule_pattern' => ['description' => 'fixed_weekly | specific_dates (mặc định fixed_weekly).', 'example' => 'fixed_weekly'],
            'rules' => ['description' => 'Lịch cố định theo tuần (day_of_week 1-7, start_time, end_time).', 'example' => [['day_of_week' => 1, 'start_time' => '18:00', 'end_time' => '19:30'], ['day_of_week' => 4, 'start_time' => '18:00', 'end_time' => '19:30']]],
            'dates' => ['description' => 'Lịch theo ngày cụ thể (date, start_time, end_time).', 'example' => [['date' => '2026-07-15', 'start_time' => '18:00', 'end_time' => '19:30']]],
        ];
    }
}
