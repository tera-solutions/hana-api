<?php

namespace App\Modules\System\Task\Services;

use App\Modules\System\Task\Models\Task;
use App\Modules\System\Task\Models\TaskAttachment;
use Illuminate\Support\Facades\Auth;

/**
 * Files attached to a task (task-management.md §VII "File đính kèm").
 */
class TaskAttachmentService
{
    public function forTask($taskId)
    {
        return TaskAttachment::with('file')->where('task_id', $taskId)->latest('id')->get();
    }

    public function create($taskId, array $data): TaskAttachment
    {
        Task::findOrFail($taskId);

        $attachment = TaskAttachment::create([
            'task_id' => $taskId,
            'file_id' => $data['file_id'],
            'created_by' => Auth::id(),
        ]);

        return $attachment->load('file');
    }

    public function delete($id): void
    {
        TaskAttachment::findOrFail($id)->delete();
    }
}
