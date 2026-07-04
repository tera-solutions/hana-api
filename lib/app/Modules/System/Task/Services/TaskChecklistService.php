<?php

namespace App\Modules\System\Task\Services;

use App\Modules\System\Task\Models\Task;
use App\Modules\System\Task\Models\TaskChecklist;
use Illuminate\Support\Facades\Auth;

/**
 * Checklist items of a task (task-management.md §VII "Checklist"). Completing an item
 * stamps the performer/time, which the task-completion gate (BR-02) reads.
 */
class TaskChecklistService
{
    public function forTask($taskId)
    {
        return TaskChecklist::where('task_id', $taskId)->orderBy('id')->get();
    }

    public function create($taskId, array $data): TaskChecklist
    {
        Task::findOrFail($taskId);

        return TaskChecklist::create([
            'task_id' => $taskId,
            'title' => $data['title'],
            'is_completed' => $data['is_completed'] ?? false,
        ]);
    }

    public function update($id, array $data): TaskChecklist
    {
        $checklist = TaskChecklist::findOrFail($id);

        if (array_key_exists('title', $data)) {
            $checklist->title = $data['title'];
        }

        if (array_key_exists('is_completed', $data)) {
            $checklist->is_completed = (bool) $data['is_completed'];
            $checklist->completed_by = $checklist->is_completed ? Auth::id() : null;
            $checklist->completed_at = $checklist->is_completed ? now() : null;
        }

        $checklist->save();

        return $checklist->fresh();
    }

    public function delete($id): void
    {
        TaskChecklist::findOrFail($id)->delete();
    }
}
