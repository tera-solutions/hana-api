<?php

namespace App\Modules\System\Task\Http\Requests;

use App\Modules\System\Task\Enums\TaskCategory;
use App\Modules\System\Task\Enums\TaskPriority;
use App\Modules\System\Task\Enums\TaskRelatedType;
use App\Modules\System\Task\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::in(TaskCategory::values())],
            'priority' => ['required', Rule::in(TaskPriority::values())],
            'status' => ['nullable', Rule::in(TaskStatus::values())],
            'progress' => ['nullable', 'integer', 'between:0,100'],

            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after:start_date'], // BR-01

            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:users,id'],
            'approver_id' => ['nullable', 'integer', 'exists:users,id'],

            'related_type' => ['nullable', Rule::in(TaskRelatedType::values())],
            'related_id' => ['nullable', 'integer', 'required_with:related_type'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tên công việc là bắt buộc.',
            'category.required' => 'Loại công việc là bắt buộc.',
            'priority.required' => 'Mức độ ưu tiên là bắt buộc.',
            'start_date.required' => 'Ngày bắt đầu là bắt buộc.',
            'due_date.required' => 'Ngày kết thúc là bắt buộc.',
            'due_date.after' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Tên công việc.', 'example' => 'Liên hệ phụ huynh đóng học phí'],
            'description' => ['description' => 'Mô tả chi tiết.', 'example' => 'Gọi điện nhắc đóng học phí tháng 6.'],
            'category' => ['description' => 'Loại công việc: general | academic | hr | finance | sales | operation.', 'example' => 'finance'],
            'priority' => ['description' => 'Mức độ ưu tiên: low | medium | high | urgent.', 'example' => 'high'],
            'status' => ['description' => 'Trạng thái khởi tạo (mặc định draft).', 'example' => 'open'],
            'progress' => ['description' => 'Phần trăm hoàn thành 0-100.', 'example' => 0],
            'start_date' => ['description' => 'Ngày bắt đầu.', 'example' => '2026-06-25'],
            'due_date' => ['description' => 'Ngày kết thúc (> ngày bắt đầu).', 'example' => '2026-06-28'],
            'assignee_id' => ['description' => 'Người thực hiện (user id).', 'example' => 1],
            'reviewer_id' => ['description' => 'Người duyệt (user id).', 'example' => 2],
            'approver_id' => ['description' => 'Người phê duyệt (user id).', 'example' => 3],
            'related_type' => ['description' => 'Loại đối tượng liên kết.', 'example' => 'parent'],
            'related_id' => ['description' => 'ID đối tượng liên kết.', 'example' => 1],
        ];
    }
}
