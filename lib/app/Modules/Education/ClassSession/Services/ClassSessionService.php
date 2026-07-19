<?php

namespace App\Modules\Education\ClassSession\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Services\ClassService;
use App\Modules\Education\ClassSession\Models\ClassSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class ClassSessionService
{
    use HandlesEntityQueries;

    private const WITH = ['classRoom', 'room', 'timetable', 'teacher', 'substituteTeacher', 'tags'];

    public function __construct(private ClassService $classes)
    {
    }

    /**
     * Paginated, searchable, filterable list (spec §3–§4).
     */
    public function paginate(array $params = []): LengthAwarePaginator
    {
        $query = ClassSession::query();

        if (! empty($params['class_id'])) {
            $query->where('class_id', $params['class_id']);
        }

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['teacher_id'])) {
            $query->where('teacher_id', $params['teacher_id']);
        }

        if (! empty($params['room_id'])) {
            $query->where('room_id', $params['room_id']);
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('session_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $query->whereDate('session_date', '<=', $params['date_to']);
        }

        if (! empty($params['tag_ids'])) {
            $tagIds = (array) $params['tag_ids'];
            $query->whereHas('tags', fn ($q) => $q->whereIn('crm_tags.id', $tagIds));
        }

        $this->applySort($query, $params, ['session_no', 'name', 'session_date', 'start_time', 'status', 'created_at'], 'session_date');

        return $query->with(self::WITH)->paginate($this->resolvePerPage($params));
    }

    public function find($id): ClassSession
    {
        return ClassSession::with(self::WITH)->findOrFail($id);
    }

    /**
     * Session detail for a read endpoint — enforces teacher ownership (403 when the
     * session's class is not the teacher's and they are not its teacher).
     */
    public function detail($id): ClassSession
    {
        $session = $this->find($id);

        return $session;
    }

    /**
     * Create a single session (spec §5).
     *
     * @throws \RuntimeException on schedule conflict
     */
    public function create(array $data): ClassSession
    {
        return DB::transaction(function () use ($data) {
            $classId = $data['class_id'];
            ClassRoom::findOrFail($classId);

            $this->assertNoConflict($classId, $data['session_date'], $data['start_time'], $data['end_time'], $data);

            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);

            $data['session_no'] = $this->nextSessionNo($classId);
            $data['code'] = $this->makeCode($classId, $data['session_no']);
            $data['status'] = ClassSession::STATUS_UPCOMING;

            $session = ClassSession::create($data);

            if ($tagIds !== null) {
                $session->tags()->sync($tagIds);
            }

            return $this->find($session->id);
        });
    }

    /**
     * Update a session (spec §7). Blocked once attendance is locked.
     *
     * @throws \RuntimeException
     */
    public function update($id, array $data): ClassSession
    {
        return DB::transaction(function () use ($id, $data) {
            $session = ClassSession::findOrFail($id);

            if ($session->attendance_locked) {
                throw new \RuntimeException('Buổi học đã chốt điểm danh, không thể cập nhật.');
            }

            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids'], $data['id'], $data['class_id'], $data['code'], $data['session_no'], $data['status']);

            $date = $data['session_date'] ?? $session->session_date->toDateString();
            $start = $data['start_time'] ?? $session->start_time;
            $end = $data['end_time'] ?? $session->end_time;

            $this->assertNoConflict($session->class_id, $date, $start, $end, $data, $session->id);

            $session->fill($data)->save();

            if ($tagIds !== null) {
                $session->tags()->sync($tagIds);
            }

            return $this->find($session->id);
        });
    }

    /**
     * Cancel a session (spec §11): no revenue, no attendance recorded.
     *
     * @throws \RuntimeException
     */
    public function cancel($id, array $data): ClassSession
    {
        $session = ClassSession::findOrFail($id);

        if ($session->status === ClassSession::STATUS_CANCELLED) {
            throw new \RuntimeException('Buổi học đã được hủy.');
        }

        if ($session->status === ClassSession::STATUS_COMPLETED) {
            throw new \RuntimeException('Buổi học đã kết thúc, không thể hủy.');
        }

        $session->update([
            'status' => ClassSession::STATUS_CANCELLED,
            'revenue_amount' => 0,
            'note' => $data['reason'],
        ]);

        return $this->find($id);
    }

    /**
     * Start an upcoming session (teaching runtime "Start Lesson" step): marks it
     * ongoing so attendance/notes can be taken and it becomes eligible for endEarly().
     *
     * @throws \RuntimeException when the session is not currently upcoming
     */
    public function start($id, array $data = []): ClassSession
    {
        $session = ClassSession::findOrFail($id);

        if ($session->status !== ClassSession::STATUS_UPCOMING) {
            throw new \RuntimeException('Chỉ có thể bắt đầu buổi học ở trạng thái sắp diễn ra.');
        }

        $session->update([
            'status' => ClassSession::STATUS_ONGOING,
            'note' => $data['note'] ?? $session->note,
        ]);

        // The class only ever flips draft/upcoming -> active in response to an
        // explicit recompute (see ClassService::computeStatus) — otherwise a
        // class whose start date has already passed just sits in "upcoming"
        // until something happens to trigger it. Starting its first session is
        // as clear a signal as any that teaching has actually begun, so nudge
        // it here too (same call TimetableService makes after creating one).
        $this->classes->recomputeStatus($session->class_id);

        return $this->find($id);
    }

    /**
     * End an in-progress session early (room-detail.md §6.2 "Dừng lại"): marks it
     * completed as of now, keeping revenue intact (unlike cancel()).
     *
     * @throws \RuntimeException when the session is not currently ongoing
     */
    public function endEarly($id, array $data = []): ClassSession
    {
        $session = ClassSession::findOrFail($id);

        if ($session->status !== ClassSession::STATUS_ONGOING) {
            throw new \RuntimeException('Chỉ có thể kết thúc sớm buổi học đang diễn ra.');
        }

        $wallClockEndTime = now()->format('H:i:s');
        // `end_time` stores time-of-day only (no date), so it can't represent a
        // session that runs past midnight. If the real clock reads earlier than
        // this session's own `start_time` — an overnight session, or the action
        // firing out of sync with the session's actual schedule — writing it
        // verbatim would produce a bogus/negative duration everywhere hours are
        // computed (timesheet, payroll, reports). Keep the session's existing
        // (originally scheduled) `end_time` instead of writing an inconsistent one.
        $endTime = $wallClockEndTime > $session->start_time ? $wallClockEndTime : $session->end_time;

        $session->update([
            'status' => ClassSession::STATUS_COMPLETED,
            'end_time' => $endTime,
            'note' => $data['note'] ?? $session->note,
        ]);

        return $this->find($id);
    }

    public function delete($id): void
    {
        $session = ClassSession::findOrFail($id);

        $session->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Reject overlapping sessions sharing the class, teacher or room at the same
     * time (spec §5 "Trùng lịch"). Cancelled sessions are ignored.
     *
     * @throws \RuntimeException
     */
    private function assertNoConflict(int $classId, string $date, string $start, string $end, array $data, ?int $excludeId = null): void
    {
        $teacherId = $data['teacher_id'] ?? null;
        $roomId = $data['room_id'] ?? null;

        $query = ClassSession::whereDate('session_date', $date)
            ->where('status', '!=', ClassSession::STATUS_CANCELLED)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->where(function ($q) use ($classId, $teacherId, $roomId) {
                $q->where('class_id', $classId);

                if ($teacherId !== null) {
                    $q->orWhere('teacher_id', $teacherId)
                        ->orWhere('substitute_teacher_id', $teacherId);
                }

                if ($roomId !== null) {
                    $q->orWhere('room_id', $roomId);
                }
            });

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \RuntimeException('Trùng lịch: phòng học, giáo viên hoặc lớp học đã có buổi trong khung giờ này.');
        }
    }

    private function nextSessionNo(int $classId): int
    {
        return (int) ClassSession::where('class_id', $classId)->max('session_no') + 1;
    }

    private function makeCode(int $classId, int $sessionNo): string
    {
        $classCode = ClassRoom::whereKey($classId)->value('code');

        return $classCode
            ? sprintf('%s-B%02d', $classCode, $sessionNo)
            : sprintf('B%02d', $sessionNo);
    }
}
