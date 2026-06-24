<?php

namespace App\Modules\System\Task\Models;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file attached to a task (table `task_attachments`).
 */
class TaskAttachment extends Model
{
    protected $table = 'task_attachments';

    protected $guarded = [];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'file_id');
    }
}
