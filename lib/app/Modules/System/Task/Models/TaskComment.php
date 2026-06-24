<?php

namespace App\Modules\System\Task\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A comment on a task (table `task_comments`).
 */
class TaskComment extends Model
{
    protected $table = 'task_comments';

    protected $guarded = [];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
