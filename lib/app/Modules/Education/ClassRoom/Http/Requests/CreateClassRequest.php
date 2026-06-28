<?php

namespace App\Modules\Education\ClassRoom\Http\Requests;

use App\Modules\Education\ClassRoom\Enums\ClassLearningType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:1000'],
            'code' => ['required', 'string', 'max:255', 'unique:edu_classes,code'],
            'course_id' => ['required', 'integer', 'exists:edu_courses,id'],
            'lesson_plan_id' => ['nullable', 'integer', 'exists:edu_lesson_plans,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:hr_teachers,id'],
            'room_id' => ['nullable', 'integer'],
            'learning_type' => ['required', Rule::in(ClassLearningType::values())],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'min_warning_capacity' => ['nullable', 'integer', 'min:0'],
            'min_capacity' => ['nullable', 'integer', 'min:0', 'gte:min_warning_capacity'],
            'max_warning_capacity' => ['nullable', 'integer', 'min:0', 'lte:max_capacity'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'use_course_curriculum' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'business_id' => ['nullable', 'integer', 'exists:sys_business,id'],

            'schedules' => ['nullable', 'array'],
            'schedules.*.weekday' => ['required', 'integer', 'between:1,7'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $data = $v->getData();
            $type = $data['learning_type'] ?? null;
            $schedules = $data['schedules'] ?? [];

            // Spec §4 validation: scheduled type requires at least one schedule.
            if ($type === 'scheduled' && empty($schedules)) {
                $v->errors()->add('schedules', 'Lớp học theo lịch cần ít nhất 1 lịch học.');
            }

            // Capacity cross-field rules (spec §4).
            $minW = $data['min_warning_capacity'] ?? null;
            $min = $data['min_capacity'] ?? null;
            $maxW = $data['max_warning_capacity'] ?? null;
            $max = $data['max_capacity'] ?? null;

            if ($minW !== null && $min !== null && $minW > $min) {
                $v->errors()->add('min_warning_capacity', 'Sĩ số cảnh báo tối thiểu không được vượt quá sĩ số tối thiểu.');
            }
            if ($maxW !== null && $max !== null && $maxW > $max) {
                $v->errors()->add('max_warning_capacity', 'Sĩ số cảnh báo tối đa không được vượt quá sĩ số tối đa.');
            }
            if ($min !== null && $max !== null && $min >= $max) {
                $v->errors()->add('min_capacity', 'Sĩ số tối thiểu phải nhỏ hơn sĩ số tối đa.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Mã lớp đã tồn tại.',
            'name.required' => 'Tên lớp không được để trống.',
            'name.max' => 'Tên lớp tối đa 255 ký tự.',
            'course_id.required' => 'Vui lòng chọn khóa học.',
            'learning_type.required' => 'Vui lòng chọn hình thức học.',
            'start_date.required' => 'Vui lòng nhập ngày khai giảng.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => ['description' => 'Tên lớp học.', 'example' => 'IELTS Foundation - Khai giảng tháng 7'],
            'avatar' => [
                'description' => 'Avatar URL.',
                'example' => 'https://cdn.hana.edu.vn/a.png',
            ],
            'code' => ['description' => 'Mã lớp (duy nhất).', 'example' => 'IELTS-F-2026-07'],
            'course_id' => ['description' => 'ID khóa học.', 'example' => 1],
            'assignee_id' => ['description' => 'ID nhân viên phụ trách (optional).', 'example' => 5],
            'teacher_id' => ['description' => 'ID giáo viên phụ trách (optional).', 'example' => 2],
            'room_id' => ['description' => 'ID phòng học (optional).', 'example' => 3],
            'learning_type' => ['description' => 'Hình thức học: scheduled | self_learning | flexible.', 'example' => 'scheduled'],
            'start_date' => ['description' => 'Ngày khai giảng (Y-m-d).', 'example' => '2026-07-01'],
            'end_date' => ['description' => 'Ngày kết thúc dự kiến (Y-m-d, optional).', 'example' => '2026-09-30'],
            'min_warning_capacity' => ['description' => 'Sĩ số cảnh báo tối thiểu.', 'example' => 5],
            'min_capacity' => ['description' => 'Sĩ số tối thiểu.', 'example' => 8],
            'max_warning_capacity' => ['description' => 'Sĩ số cảnh báo tối đa.', 'example' => 18],
            'max_capacity' => ['description' => 'Sĩ số tối đa.', 'example' => 20],
            'use_course_curriculum' => ['description' => 'Sao chép chương trình học từ khóa học mẫu.', 'example' => true],
            'description' => ['description' => 'Mô tả lớp học.'],
            'schedules' => [
                'description' => 'Danh sách lịch học (array).',
                'example' => [
                    ['weekday' => 2, 'start_time' => '19:00', 'end_time' => '20:30'],
                ],
            ],
            'schedules[].weekday' => ['description' => 'Thứ trong tuần (1=T2 … 7=CN).', 'example' => 2],
            'schedules[].start_time' => ['description' => 'Giờ bắt đầu (HH:MM).', 'example' => '19:00'],
            'schedules[].end_time' => ['description' => 'Giờ kết thúc (HH:MM).', 'example' => '20:30'],
        ];
    }
}
