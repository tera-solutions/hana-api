<?php

namespace App\Modules\Education\Lesson\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\Lesson\Models\LessonActivity;
use App\Modules\Education\Lesson\Models\LessonHistory;
use App\Modules\Education\LessonPlan\Models\LessonPlan;
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
        if (! empty($params['session_id'])) {
            $query->where('session_id', $params['session_id']);
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

        $this->applySort($query, $params, ['lesson_no', 'lesson_date', 'start_time', 'status', 'created_at']);

        return $query->with(['classRoom', 'teacher', 'room'])->paginate($this->resolvePerPage($params));
    }

    public function find($id): Lesson
    {
        return Lesson::with(['classRoom', 'teacher', 'room', 'activities'])->findOrFail($id);
    }

    public function detail($id): array
    {
        $lesson = Lesson::with(['classRoom', 'teacher', 'room', 'histories', 'activities', 'lessonPlanLesson.materials'])->findOrFail($id);

        return ['lesson' => $lesson];
    }

    /**
     * Create the Lesson paired 1:1 with a freshly generated ClassSession, snapshotting
     * the next unconsumed lesson-plan template in sequence (lesson.md §7, BR001/BR002/BR003).
     * Called by TimetableService right after it creates the session — replaces the old
     * bulk generate() that read a class's ClassSchedule rows (removed with that module).
     *
     * No-op when the class has no (published) lesson plan, or the plan is exhausted.
     */
    /**
     * Materialize a Lesson (curriculum snapshot) for $session from the given
     * lesson plan, at the moment the teacher starts the session and picks
     * which plan it follows — not at session-creation time, since a class may
     * have several plans to choose from (or the session may need none at all,
     * e.g. an exam day).
     *
     * @param  int|null  $lessonPlanLessonId  Explicit template (bài học) to use —
     *                                        when given, overrides the "next unused"
     *                                        pick below. Must belong to $lessonPlanId
     *                                        and not already be consumed by another Lesson.
     *
     * @throws \RuntimeException when the plan isn't linked to the class, isn't
     *                           published, has no template left to consume, or
     *                           the requested template is invalid/already used.
     */
    public function createFromSessionWithPlan(ClassSession $session, int $lessonPlanId, ?int $lessonPlanLessonId = null): Lesson
    {
        $class = ClassRoom::findOrFail($session->class_id);

        if (! $class->lessonPlans()->where('edu_lesson_plans.id', $lessonPlanId)->exists()) {
            throw new \RuntimeException('Giáo án này chưa được gắn với lớp học.');
        }

        $plan = LessonPlan::with('lessons.activities')->find($lessonPlanId);
        if (! $plan || $plan->status !== LessonPlan::STATUS_PUBLISHED) {
            throw new \RuntimeException('Giáo án chưa được xuất bản.');
        }

        // Whichever templates from THIS plan already became a Lesson (in any
        // session) are spent — pick the next unused one, in plan order.
        $usedTemplateIds = Lesson::where('lesson_plan_id', $plan->id)
            ->whereNotNull('lesson_plan_lesson_id')
            ->pluck('lesson_plan_lesson_id');

        if ($lessonPlanLessonId) {
            $template = $plan->lessons->firstWhere('id', $lessonPlanLessonId);
            if (! $template) {
                throw new \RuntimeException('Bài học này không thuộc giáo án đã chọn.');
            }
            if ($usedTemplateIds->contains($lessonPlanLessonId)) {
                throw new \RuntimeException('Bài học này đã được sử dụng cho một buổi học khác.');
            }
        } else {
            $template = $plan->lessons
                ->whereNotIn('id', $usedTemplateIds)
                ->sortBy('lesson_no')
                ->first();
        }

        if (! $template) {
            throw new \RuntimeException('Giáo án đã hết buổi học để sử dụng.');
        }

        $nextLessonNo = ((int) Lesson::where('class_room_id', $session->class_id)->max('lesson_no')) + 1;

        $lesson = Lesson::create([
            'class_room_id' => $session->class_id,
            'session_id' => $session->id,
            'lesson_plan_id' => $plan->id,
            'lesson_plan_lesson_id' => $template->id,
            'lesson_no' => $nextLessonNo,
            'lesson_title' => $template->lesson_title,
            'lesson_date' => $session->session_date,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'room_id' => $session->room_id,
            'teacher_id' => $session->teacher_id,
            'objective' => $template->objective,
            'vocabulary' => $template->vocabulary,
            'grammar' => $template->grammar,
            'homework' => $template->homework,
            'status' => Lesson::STATUS_SCHEDULED,
        ]);

        foreach ($template->activities as $activity) {
            $lesson->activities()->create([
                'sort_order' => $activity->sort_order,
                'avatar' => $activity->avatar,
                'title' => $activity->title,
                'description' => $activity->description,
                'duration' => $activity->duration,
                'status' => LessonActivity::STATUS_PENDING,
            ]);
        }

        return $lesson;
    }

    /**
     * Change which lesson plan / template this Lesson follows, after it's
     * already been materialized (SessionRuntime "Đổi giáo án / bài học") —
     * reuses the same plan/template validation as createFromSessionWithPlan(),
     * then re-snapshots the curriculum fields and replaces the activities
     * wholesale, since there's no stable identity to merge old activity
     * progress against a different template's activities.
     *
     * @param  int|null  $lessonPlanLessonId  Explicit template to use; falls
     *                                        back to the plan's next unused
     *                                        template (by lesson_no) when omitted.
     *
     * @throws \RuntimeException when the lesson is completed/locked, the plan
     *                           isn't linked to the class, isn't published, has
     *                           no template left, or the requested template is
     *                           invalid/already used by another Lesson.
     */
    public function changePlan($id, array $data): Lesson
    {
        return DB::transaction(function () use ($id, $data) {
            $lesson = Lesson::findOrFail($id);

            $this->authorizeLesson($lesson);
            $this->assertMutable($lesson);

            $class = ClassRoom::findOrFail($lesson->class_room_id);
            $planId = (int) $data['lesson_plan_id'];

            if (! $class->lessonPlans()->where('edu_lesson_plans.id', $planId)->exists()) {
                throw new \RuntimeException('Giáo án này chưa được gắn với lớp học.');
            }

            $plan = LessonPlan::with('lessons.activities')->find($planId);
            if (! $plan || $plan->status !== LessonPlan::STATUS_PUBLISHED) {
                throw new \RuntimeException('Giáo án chưa được xuất bản.');
            }

            // Templates already spent by OTHER lessons — this lesson's own
            // current template must stay pickable when re-choosing the same plan.
            $usedTemplateIds = Lesson::where('lesson_plan_id', $plan->id)
                ->where('id', '!=', $lesson->id)
                ->whereNotNull('lesson_plan_lesson_id')
                ->pluck('lesson_plan_lesson_id');

            $lessonPlanLessonId = $data['lesson_plan_lesson_id'] ?? null;

            if ($lessonPlanLessonId) {
                $template = $plan->lessons->firstWhere('id', $lessonPlanLessonId);
                if (! $template) {
                    throw new \RuntimeException('Bài học này không thuộc giáo án đã chọn.');
                }
                if ($usedTemplateIds->contains($lessonPlanLessonId)) {
                    throw new \RuntimeException('Bài học này đã được sử dụng cho một buổi học khác.');
                }
            } else {
                $template = $plan->lessons
                    ->whereNotIn('id', $usedTemplateIds)
                    ->sortBy('lesson_no')
                    ->first();
            }

            if (! $template) {
                throw new \RuntimeException('Giáo án đã hết buổi học để sử dụng.');
            }

            $oldPlanName = $lesson->lessonPlan?->plan_name ?? "#{$lesson->lesson_plan_id}";

            $lesson->update([
                'lesson_plan_id' => $plan->id,
                'lesson_plan_lesson_id' => $template->id,
                'lesson_title' => $template->lesson_title,
                'objective' => $template->objective,
                'vocabulary' => $template->vocabulary,
                'grammar' => $template->grammar,
                'homework' => $template->homework,
            ]);

            $lesson->activities()->delete();
            foreach ($template->activities as $activity) {
                $lesson->activities()->create([
                    'sort_order' => $activity->sort_order,
                    'avatar' => $activity->avatar,
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'duration' => $activity->duration,
                    'status' => LessonActivity::STATUS_PENDING,
                ]);
            }

            $this->log($lesson, 'change_plan', $oldPlanName, $plan->plan_name);

            return $this->find($lesson->id);
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
     * Update the status of a single activity within a lesson.
     *
     * @throws \RuntimeException
     */
    public function updateActivityStatus($activityId, array $data): LessonActivity
    {
        return DB::transaction(function () use ($activityId, $data) {
            $activity = LessonActivity::with('lesson')->findOrFail($activityId);
            $lesson = $activity->lesson;

            $this->authorizeLesson($lesson);
            $this->assertMutable($lesson);

            $activity->update(['status' => $data['status']]);

            return $activity;
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

    private function authorizeLesson(Lesson $lesson): void {}

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
