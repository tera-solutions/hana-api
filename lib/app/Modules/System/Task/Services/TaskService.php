<?php

namespace App\Modules\System\Task\Services;

use App\Modules\System\Task\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * Task management business logic (task-management.md). Enforces the date rule (BR-01),
 * the checklist/progress gates on status changes (BR-02/BR-03), the assignee-only
 * progress rule (BR-04) and the reviewer self-approval rule (BR-05).
 */
class TaskService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['checklists', 'comments', 'attachments'];

    public function paginate(array $params = [])
    {
        $query = Task::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('task_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'priority', 'category', 'assignee_id', 'creator_id', 'reviewer_id', 'related_type', 'related_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['due_from'])) {
            $query->whereDate('due_date', '>=', $params['due_from']);
        }
        if (! empty($params['due_to'])) {
            $query->whereDate('due_date', '<=', $params['due_to']);
        }

        $this->applySort($query, $params, ['task_code', 'priority', 'due_date', 'progress', 'status', 'created_at']);

        return $query->withCount(['checklists', 'comments', 'attachments'])->paginate($this->resolvePerPage($params));
    }

    public function find($id): Task
    {
        return Task::with(self::RELATIONS)->findOrFail($id);
    }

    public function create(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            $task = new Task($data);
            $task->task_code = $this->generateCode();
            $task->status = $data['status'] ?? Task::STATUS_DRAFT;
            $task->progress = $data['progress'] ?? 0;
            $task->creator_id = $data['creator_id'] ?? Auth::id();
            $task->save();

            return $this->find($task->id);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function update($id, array $data): Task
    {
        return DB::transaction(function () use ($id, $data) {
            $task = Task::findOrFail($id);

            $this->assertDateOrder($data, $task);

            if (array_key_exists('progress', $data) && (int) $data['progress'] !== (int) $task->progress) {
                $this->assertAssignee($task); // BR-04
            }

            if (! empty($data['status']) && $data['status'] !== $task->status) {
                $this->assertTransitionAllowed($task, $data['status'], (int) ($data['progress'] ?? $task->progress));
                $data['completed_at'] = $data['status'] === Task::STATUS_COMPLETED ? now() : null;
            }

            $task->update($data);

            return $this->find($id);
        });
    }

    public function delete($id): void
    {
        Task::findOrFail($id)->delete();
    }

    // ── Guards ──────────────────────────────────────────────────────────────

    /**
     * @throws \RuntimeException
     */
    private function assertTransitionAllowed(Task $task, string $to, int $progress): void
    {
        if ($to === Task::STATUS_PENDING_REVIEW && $progress < 100) {
            throw new \RuntimeException('Không thể chuyển sang chờ duyệt khi tiến độ chưa đạt 100%.'); // BR-03
        }

        if ($to === Task::STATUS_COMPLETED) {
            if ($progress < 100) {
                throw new \RuntimeException('Không thể hoàn thành khi tiến độ chưa đạt 100%.'); // BR-03
            }
            if ($task->checklists()->where('is_completed', false)->exists()) {
                throw new \RuntimeException('Không thể hoàn thành khi checklist chưa hoàn tất.'); // BR-02
            }
            if (! $this->isAdmin() && $task->assignee_id && (int) $task->assignee_id === (int) Auth::id()) {
                throw new \RuntimeException('Người duyệt không được tự duyệt công việc của mình.'); // BR-05
            }
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function assertAssignee(Task $task): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if (! $task->assignee_id || (int) $task->assignee_id !== (int) Auth::id()) {
            throw new \RuntimeException('Chỉ người được giao việc mới được cập nhật tiến độ.'); // BR-04
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function assertDateOrder(array $data, Task $task): void
    {
        $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : $task->start_date;
        $due = isset($data['due_date']) ? Carbon::parse($data['due_date']) : $task->due_date;

        if ($start && $due && $due->lte($start)) {
            throw new \RuntimeException('Ngày kết thúc phải lớn hơn ngày bắt đầu.'); // BR-01
        }
    }

    private function isAdmin(): bool
    {
        return (bool) (Auth::user()->is_admin ?? false);
    }

    private function generateCode(): string
    {
        $next = (int) (Task::withTrashed()->max('id') ?? 0) + 1;

        return 'TASK'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
