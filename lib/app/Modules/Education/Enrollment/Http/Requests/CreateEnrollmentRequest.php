<?php

namespace App\Modules\Education\Enrollment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:edu_students,id'],
            'course_id' => ['required', 'integer', 'exists:edu_courses,id'],
            'class_id' => ['required', 'integer', 'exists:edu_classes,id'],
            'sales_id' => ['nullable', 'integer', 'exists:users,id'],

            'enrolled_at' => ['nullable', 'date'],

            'total_lessons' => ['required', 'integer', 'min:1'],
            'price_per_lesson' => ['required', 'numeric', 'min:0'],

            'discount_percent' => ['nullable', 'numeric', 'between:0,100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'bonus_lessons' => ['nullable', 'integer', 'min:0'],

            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],

            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.required' => 'Vui lòng chọn học viên.',
            'student_id.exists' => 'Học viên không tồn tại.',
            'course_id.required' => 'Vui lòng chọn khóa học.',
            'class_id.required' => 'Vui lòng chọn lớp học.',
            'total_lessons.required' => 'Vui lòng nhập số buổi học.',
            'total_lessons.min' => 'Số buổi học phải lớn hơn 0.',
            'price_per_lesson.required' => 'Vui lòng nhập đơn giá mỗi buổi.',
            'discount_percent.between' => 'Phần trăm giảm giá phải từ 0 đến 100.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'student_id' => ['description' => 'ID học viên.', 'example' => 1],
            'course_id' => ['description' => 'ID khóa học.', 'example' => 1],
            'class_id' => ['description' => 'ID lớp học (phải thuộc khóa học đã chọn).', 'example' => 1],
            'sales_id' => ['description' => 'ID nhân viên tư vấn (optional).', 'example' => 5],
            'enrolled_at' => ['description' => 'Ngày ghi danh (Y-m-d, mặc định hôm nay).', 'example' => '2026-07-01'],
            'total_lessons' => ['description' => 'Số buổi học đăng ký.', 'example' => 24],
            'price_per_lesson' => ['description' => 'Đơn giá mỗi buổi.', 'example' => 250000],
            'discount_percent' => ['description' => 'Giảm theo phần trăm (0–100).', 'example' => 10],
            'discount_amount' => ['description' => 'Giảm trực tiếp (số tiền).', 'example' => 100000],
            'bonus_lessons' => ['description' => 'Số buổi học tặng thêm.', 'example' => 2],
            'paid_amount' => ['description' => 'Số tiền đã thanh toán ngay khi ghi danh.', 'example' => 3000000],
            'payment_method' => ['description' => 'Phương thức thanh toán (cash, transfer…).', 'example' => 'cash'],
            'note' => ['description' => 'Ghi chú.'],
        ];
    }
}
