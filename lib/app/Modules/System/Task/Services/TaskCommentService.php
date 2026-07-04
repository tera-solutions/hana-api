<?php

namespace App\Modules\System\Task\Services;

use App\Modules\System\Task\Models\Task;
use App\Modules\System\Task\Models\TaskComment;
use Illuminate\Support\Facades\Auth;

/**
 * Internal discussion on a task (task-management.md §VII "Bình luận").
 */
class TaskCommentService
{
    public function forTask($taskId)
    {
        return TaskComment::where('task_id', $taskId)->latest('id')->get();
    }

    public function create($taskId, array $data): TaskComment
    {
        Task::findOrFail($taskId);

        return TaskComment::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'comment' => $data['comment'],
        ]);
    }
}
