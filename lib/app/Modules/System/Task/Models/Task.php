<?php

namespace App\Modules\System\Task\Models;

use App\Modules\System\Task\Enums\TaskPriority;
use App\Modules\System\Task\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Package\Database\Concerns\HasAuditFields;

/**
 * An internal work item (table `task_tasks`, task-management.md §XII).
 */
class Task extends Model
{
    use HasAuditFields;
    use SoftDeletes;

    protected $table = 'task_tasks';

    protected $guarded = [];

    protected $casts = [
        'progress' => 'integer',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public const STATUS_DRAFT = TaskStatus::Draft->value;

    public const STATUS_OPEN = TaskStatus::Open->value;

    public const STATUS_IN_PROGRESS = TaskStatus::InProgress->value;

    public const STATUS_PENDING_REVIEW = TaskStatus::PendingReview->value;

    public const STATUS_COMPLETED = TaskStatus::Completed->value;

    public const STATUS_REJECTED = TaskStatus::Rejected->value;

    public const STATUS_CANCELLED = TaskStatus::Cancelled->value;

    public const STATUS_OVERDUE = TaskStatus::Overdue->value;

    public const PRIORITY_LOW = TaskPriority::Low->value;

    public const PRIORITY_MEDIUM = TaskPriority::Medium->value;

    public const PRIORITY_HIGH = TaskPriority::High->value;

    public const PRIORITY_URGENT = TaskPriority::Urgent->value;

    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class, 'task_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id')->latest('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'task_id')->latest('id');
    }
}
