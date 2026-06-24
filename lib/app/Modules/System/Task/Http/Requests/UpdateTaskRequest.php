<?php

namespace App\Modules\System\Task\Http\Requests;

use App\Modules\System\Task\Enums\TaskCategory;
use App\Modules\System\Task\Enums\TaskPriority;
use App\Modules\System\Task\Enums\TaskRelatedType;
use App\Modules\System\Task\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['sometimes', Rule::in(TaskCategory::values())],
            'priority' => ['sometimes', Rule::in(TaskPriority::values())],
            'status' => ['sometimes', Rule::in(TaskStatus::values())],
            'progress' => ['sometimes', 'integer', 'between:0,100'],

            'start_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date', 'after:start_date'],

            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'reviewer_id' => ['nullable', 'integer', 'exists:users,id'],
            'approver_id' => ['nullable', 'integer', 'exists:users,id'],

            'related_type' => ['nullable', Rule::in(TaskRelatedType::values())],
            'related_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu.',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'title' => ['description' => 'Tên công việc.', 'example' => 'Liên hệ phụ huynh (cập nhật)'],
            'status' => ['description' => 'Trạng thái: draft|open|in_progress|pending_review|completed|rejected|cancelled.', 'example' => 'in_progress'],
            'progress' => ['description' => 'Phần trăm hoàn thành 0-100 (chỉ người được giao cập nhật).', 'example' => 50],
            'priority' => ['description' => 'Mức độ ưu tiên.', 'example' => 'urgent'],
            'due_date' => ['description' => 'Ngày kết thúc (> ngày bắt đầu).', 'example' => '2026-06-30'],
            'assignee_id' => ['description' => 'Người thực hiện.', 'example' => 1],
        ];
    }
}
