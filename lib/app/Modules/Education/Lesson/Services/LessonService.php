<?php

namespace App\Modules\Education\Lesson\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\Lesson\Models\LessonHistory;
use App\Modules\Education\LessonPlan\Models\LessonPlan;
use App\Modules\Education\Support\TeacherScope;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class LessonService
{
    use HandlesEntityQueries;

    /**
     * Paginated, filterable list (lesson.md §14).
     */
    public function paginate(array $params = [])
    {
        $query = Lesson::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where('lesson_title', 'like', "%{$search}%");
        }

        if (! empty($params['class_room_id'])) {
            $query->where('class_room_id', $params['class_room_id']);
        }
        if (! empty($params['lesson_plan_id'])) {
            $query->where('lesson_plan_id', $params['lesson_plan_id']);
        }
        if (! empty($params['teacher_id'])) {
            $query->where('teacher_id', $params['teacher_id']);
        }
        if (! empty($params['room_id'])) {
            $query->where('room_id', $params['room_id']);
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['lesson_date'])) {
            $query->whereDate('lesson_date', $params['lesson_date']);
        }
        if (! empty($params['date_from'])) {
            $query->whereDate('lesson_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('lesson_date', '<=', $params['date_to']);
        }
        if (! empty($params['branch_id'])) {
            $query->whereHas('room', fn ($q) => $q->where('branch_id', $params['branch_id']));
        }

        if ($scope = TeacherScope::current()) {
            $scope->constrainByClass($query, 'class_room_id');
        }

        $this->applySort($query, $params, ['lesson_no', 'lesson_date', 'start_time', 'status', 'created_at']);

        return $query->with(['classRoom', 'teacher', 'room'])->paginate($this->resolvePerPage($params));
    }

    public function find($id): Lesson
    {
        return Lesson::with(['classRoom', 'teacher', 'room'])->findOrFail($id);
    }

    public function detail($id): array
    {
        $lesson = Lesson::with(['classRoom', 'teacher', 'room', 'histories', 'lessonPlanLesson.materials'])->findOrFail($id);

        if ($scope = TeacherScope::current()) {
            $scope->authorizeClass((int) $lesson->class_room_id);
        }

        return ['lesson' => $lesson];
    }

    /**
     * Generate lessons for a class from its lesson plan (lesson.md §7).
     * Snapshots each template (BR001/BR002) and auto-numbers them (BR003).
     *
     * @return array{created: int, skipped: int}
     *
     * @throws \RuntimeException
     */
    public function generate($classId, array $data): array
    {
        return DB::transaction(function () use ($classId, $data) {
            $class = ClassRoom::where('id', $classId)->first();

            if (! $class) {
                throw new \RuntimeException('Lớp học không tồn tại.');
            }

            if ($scope = TeacherScope::current()) {
                $scope->authorizeClass((int) $classId);
            }

            if (! $class->lesson_plan_id) {
                throw new \RuntimeException('Lớp học chưa được gắn giáo án.');
            }

            $plan = LessonPlan::with('lessons')->findOrFail($class->lesson_plan_id);

            if ($plan->status !== LessonPlan::STATUS_PUBLISHED) {
                throw new \RuntimeException('Chỉ giáo án đã xuất bản mới có thể sinh buổi học.');
            }
            if ($plan->lessons->isEmpty()) {
                throw new \RuntimeException('Giáo án không có buổi học để sinh.');
            }

            $schedules = ClassSchedule::where('class_id', $classId)->get();
            if ($schedules->isEmpty()) {
                throw new \RuntimeException('Lớp học chưa có lịch học để sinh buổi học.');
            }

            if (! empty($data['override'])) {
                Lesson::where('class_room_id', $classId)
                    ->whereNotIn('status', [Lesson::STATUS_COMPLETED, Lesson::STATUS_LOCKED])
                    ->delete();
            }

            $templates = $plan->lessons->values();
            $created = 0;
            $skipped = 0;
            $index = 0;

            foreach (CarbonPeriod::create($data['from_date'], $data['from_date'].' +1 year') as $date) {
                if ($index >= $templates->count()) {
                    break;
                }

                $weekday = $date->dayOfWeekIso; // 1 (Mon) … 7 (Sun)

                foreach ($schedules->where('weekday', $weekday) as $schedule) {
                    if ($index >= $templates->count()) {
                        break;
                    }

                    $template = $templates[$index];
                    $lessonNo = $template->lesson_no;

                    $exists = Lesson::where('class_room_id', $classId)->where('lesson_no', $lessonNo)->exists();
                    if ($exists) {
                        $skipped++;
                        $index++;

                        continue;
                    }

                    Lesson::create([
                        'class_room_id' => $classId,
                        'lesson_plan_id' => $plan->id,
                        'lesson_plan_lesson_id' => $template->id,
                        'lesson_no' => $lessonNo,
                        'lesson_title' => $template->lesson_title,
                        'lesson_date' => $date->toDateString(),
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'room_id' => $class->room_id,
                        'teacher_id' => $class->teacher_id,
                        'objective' => $template->objective,
                        'vocabulary' => $template->vocabulary,
                        'grammar' => $template->grammar,
                        'activities' => $template->activities,
                        'homework' => $template->homework,
                        'status' => Lesson::STATUS_SCHEDULED,
                    ]);

                    $created++;
                    $index++;
                }
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    /**
     * Update editable fields (lesson.md §8): teacher_id, lesson_note, status.
     * Date/time/room changes go through reschedule; cancel/lock/unlock keep
     * their own endpoints, so only the plain progression statuses are accepted.
     *
     * @throws \RuntimeException
     */
    public function update($id, array $data): Lesson
    {
        return DB::transaction(function () use ($id, $data) {
            $lesson = Lesson::findOrFail($id);

            $this->authorizeLesson($lesson);
            $this->assertMutable($lesson);

            // BR006: a teacher change must not clash with the teacher's other lessons.
            if (array_key_exists('teacher_id', $data) && (int) $data['teacher_id'] !== (int) $lesson->teacher_id) {
                $this->assertNoTeacherConflict($lesson, (int) $data['teacher_id'], $lesson->lesson_date->toDateString(), $lesson->start_time, $lesson->end_time);

                $this->log($lesson, 'change_teacher', (string) $lesson->teacher_id, (string) $data['teacher_id']);
            }

            if (array_key_exists('status', $data) && $data['status'] !== $lesson->status) {
                $this->log($lesson, 'change_status', $lesson->status, $data['status']);
            }

            $lesson->update([
                'teacher_id' => $data['teacher_id'] ?? $lesson->teacher_id,
                'lesson_note' => $data['lesson_note'] ?? $lesson->lesson_note,
                'status' => $data['status'] ?? $lesson->status,
            ]);

            return $this->find($lesson->id);
        });
    }

    /**
     * Reschedule a lesson (lesson.md §9): enforces teacher/room conflicts and no
     * past dates (BR006/BR007/BR008).
     *
     * @throws \RuntimeException
     */
    public function reschedule($id, array $data): Lesson
    {
        return DB::transaction(function () use ($id, $data) {
            $lesson = Lesson::findOrFail($id);

            $this->authorizeLesson($lesson);
            $this->assertMutable($lesson);

            $date = $data['lesson_date'];
            $start = $this->normalizeTime($data['start_time']);
            $end = $this->normalizeTime($data['end_time']);
            $roomId = $data['room_id'] ?? $lesson->room_id;

            // BR008: cannot move a lesson into the past.
            if ($date < now()->toDateString()) {
                throw new \RuntimeException('Không thể đổi lịch về quá khứ.');
            }

            $this->assertNoTeacherConflict($lesson, (int) $lesson->teacher_id, $date, $start, $end);
            $this->assertNoRoomConflict($lesson, (int) $roomId, $date, $start, $end);

            $old = "{$lesson->lesson_date->toDateString()} {$lesson->start_time}-{$lesson->end_time} room:{$lesson->room_id}";
            $new = "{$date} {$start}-{$end} room:{$roomId}";

            $lesson->update([
                'lesson_date' => $date,
                'start_time' => $start,
                'end_time' => $end,
                'room_id' => $roomId,
            ]);

            $this->log($lesson, 'reschedule', $old, $new);

            return $this->find($lesson->id);
        });
    }

    /**
     * Cancel a lesson (lesson.md §10).
     *
     * @throws \RuntimeException
     */
    public function cancel($id, array $data): Lesson
    {
        return DB::transaction(function () use ($id, $data) {
            $lesson = Lesson::findOrFail($id);

            $this->authorizeLesson($lesson);

            // BR010: a completed (or locked) lesson cannot be cancelled.
            if (in_array($lesson->status, [Lesson::STATUS_COMPLETED, Lesson::STATUS_LOCKED], true)) {
                throw new \RuntimeException('Buổi học đã hoàn thành hoặc đã khóa, không thể hủy.');
            }
            if ($lesson->status === Lesson::STATUS_CANCELLED) {
                throw new \RuntimeException('Buổi học đã được hủy.');
            }

            $lesson->update(['status' => Lesson::STATUS_CANCELLED]);

            $this->log($lesson, 'cancel', null, null, $data['reason'] ?? null);

            return $this->find($lesson->id);
        });
    }

    /**
     * Manually complete a lesson before its scheduled end time (lesson.md §6).
     * Normal completion is automatic via autoComplete(); this covers early
     * wrap-up so the lesson can then be locked.
     *
     * @throws \RuntimeException
     */
    public function complete($id): Lesson
    {
        return DB::transaction(function () use ($id) {
            $lesson = Lesson::findOrFail($id);

            $this->authorizeLesson($lesson);

            if (in_array($lesson->status, [Lesson::STATUS_CANCELLED, Lesson::STATUS_LOCKED, Lesson::STATUS_COMPLETED], true)) {
                throw new \RuntimeException('Buổi học không thể chuyển sang hoàn thành từ trạng thái hiện tại.');
            }

            $lesson->update(['status' => Lesson::STATUS_COMPLETED, 'completed_at' => now()]);

            $this->log($lesson, 'complete');

            return $this->find($lesson->id);
        });
    }

    /**
     * Lock a lesson after completion (lesson.md §11, BR011).
     *
     * @throws \RuntimeException
     */
    public function lock($id): Lesson
    {
        return DB::transaction(function () use ($id) {
            $lesson = Lesson::findOrFail($id);

            $this->authorizeLesson($lesson);

            if ($lesson->isLocked()) {
                throw new \RuntimeException('Buổi học đã được khóa.');
            }
            // BR011: only a completed lesson can be locked.
            if (! $lesson->isCompleted()) {
                throw new \RuntimeException('Chỉ có thể khóa buổi học đã hoàn thành.');
            }

            $lesson->update(['status' => Lesson::STATUS_LOCKED, 'locked_at' => now()]);

            $this->log($lesson, 'lock');

            return $this->find($lesson->id);
        });
    }

    /**
     * Unlock a lesson (lesson.md §12). Requires a reason; the unlock is audited.
     *
     * @throws \RuntimeException
     */
    public function unlock($id, array $data): Lesson
    {
        return DB::transaction(function () use ($id, $data) {
            $lesson = Lesson::findOrFail($id);

            $this->authorizeLesson($lesson);

            if (! $lesson->isLocked()) {
                throw new \RuntimeException('Buổi học chưa bị khóa.');
            }

            // Restart the auto-lock window so the unlocked lesson isn't re-locked
            // on the next run (lesson.md §11/§12).
            $lesson->update(['status' => Lesson::STATUS_COMPLETED, 'locked_at' => null, 'completed_at' => now()]);

            $this->log($lesson, 'unlock', null, null, $data['reason'] ?? null);

            return $this->find($lesson->id);
        });
    }

    // ── Auto-progression (lesson.md §6, §11) ────────────────────────────────────

    /**
     * Complete every lesson whose end time has passed. Only the active states
     * advance; cancelled/locked/completed are left as-is. completed_at is the
     * lesson's real end moment so the lock window counts from when it finished.
     * Idempotent — safe to re-run.
     *
     * @return int number of lessons completed
     */
    public function autoComplete(): int
    {
        $now = now();

        $lessons = Lesson::whereIn('status', [Lesson::STATUS_SCHEDULED, Lesson::STATUS_CONFIRMED, Lesson::STATUS_IN_PROGRESS])
            ->where(function ($q) use ($now) {
                $q->whereDate('lesson_date', '<', $now->toDateString())
                    ->orWhere(function ($q2) use ($now) {
                        $q2->whereDate('lesson_date', $now->toDateString())
                            ->where('end_time', '<=', $now->format('H:i:s'));
                    });
            })
            ->get();

        foreach ($lessons as $lesson) {
            $endsAt = $lesson->lesson_date->copy()->setTimeFromTimeString($lesson->end_time);
            $lesson->update(['status' => Lesson::STATUS_COMPLETED, 'completed_at' => $endsAt]);
            $this->log($lesson, 'auto_complete');
        }

        return $lessons->count();
    }

    /**
     * Lock every completed lesson finished longer than the configured window ago
     * (lesson.md §11). Idempotent — safe to re-run.
     *
     * @return int number of lessons locked
     */
    public function autoLock(): int
    {
        $days = (int) config('education.lesson_auto_lock_days', 7);
        $cutoff = now()->subDays($days);

        $lessons = Lesson::where('status', Lesson::STATUS_COMPLETED)
            ->whereNotNull('completed_at')
            ->where('completed_at', '<=', $cutoff)
            ->get();

        foreach ($lessons as $lesson) {
            $lesson->update(['status' => Lesson::STATUS_LOCKED, 'locked_at' => now()]);
            $this->log($lesson, 'auto_lock', null, null, "Tự động khóa sau {$days} ngày.");
        }

        return $lessons->count();
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    private function authorizeLesson(Lesson $lesson): void
    {
        if ($scope = TeacherScope::current()) {
            $scope->authorizeClass((int) $lesson->class_room_id);
        }
    }

    /**
     * @throws \RuntimeException BR004 (completed) / BR005 (locked).
     */
    private function assertMutable(Lesson $lesson): void
    {
        if ($lesson->isCompleted()) {
            throw new \RuntimeException('Buổi học đã hoàn thành, không thể chỉnh sửa.');
        }
        if ($lesson->isLocked()) {
            throw new \RuntimeException('Buổi học đã khóa, không thể chỉnh sửa.');
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function assertNoTeacherConflict(Lesson $lesson, ?int $teacherId, string $date, string $start, string $end): void
    {
        if (! $teacherId) {
            return;
        }

        $clash = $this->overlapQuery($lesson->id, $date, $start, $end)
            ->where('teacher_id', $teacherId)
            ->exists();

        if ($clash) {
            throw new \RuntimeException('Giáo viên đã có buổi học khác trùng khung giờ này.');
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function assertNoRoomConflict(Lesson $lesson, ?int $roomId, string $date, string $start, string $end): void
    {
        if (! $roomId) {
            return;
        }

        $clash = $this->overlapQuery($lesson->id, $date, $start, $end)
            ->where('room_id', $roomId)
            ->exists();

        if ($clash) {
            throw new \RuntimeException('Phòng học đã có buổi học khác trùng khung giờ này.');
        }
    }

    /**
     * Lessons (excluding $excludeId and cancelled) overlapping the given slot.
     */
    private function overlapQuery(int $excludeId, string $date, string $start, string $end)
    {
        return Lesson::where('id', '!=', $excludeId)
            ->whereDate('lesson_date', $date)
            ->where('status', '!=', Lesson::STATUS_CANCELLED)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);
    }

    private function normalizeTime(string $time): string
    {
        return substr($time, 0, 5).':00';
    }

    private function log(Lesson $lesson, string $action, ?string $old = null, ?string $new = null, ?string $reason = null): void
    {
        LessonHistory::create([
            'lesson_id' => $lesson->id,
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'reason' => $reason,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
            'created_at' => now(),
        ]);
    }
}
