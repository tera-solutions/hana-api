<?php

namespace App\Modules\System\Task\Http\Resources;

use App\Modules\System\Task\Enums\TaskCategory;
use App\Modules\System\Task\Enums\TaskPriority;
use App\Modules\System\Task\Enums\TaskStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'task_code' => $this->task_code,
            'title' => $this->title,
            'description' => $this->description,

            'category' => $this->category,
            'category_label' => TaskCategory::tryFrom((string) $this->category)?->label(),
            'priority' => $this->priority,
            'priority_label' => TaskPriority::tryFrom((string) $this->priority)?->label(),
            'status' => $this->status,
            'status_label' => TaskStatus::tryFrom((string) $this->status)?->label(),
            'progress' => $this->progress,

            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,

            'creator_id' => $this->creator_id,
            'assignee_id' => $this->assignee_id,
            'reviewer_id' => $this->reviewer_id,
            'approver_id' => $this->approver_id,

            'related_type' => $this->related_type,
            'related_id' => $this->related_id,

            'checklists_count' => $this->whenCounted('checklists'),
            'comments_count' => $this->whenCounted('comments'),
            'attachments_count' => $this->whenCounted('attachments'),

            'checklists' => TaskChecklistResource::collection($this->whenLoaded('checklists')),
            'comments' => TaskCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => TaskAttachmentResource::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
