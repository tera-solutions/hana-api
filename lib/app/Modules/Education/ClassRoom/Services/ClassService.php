<?php

namespace App\Modules\Education\ClassRoom\Services;

use App\Modules\Education\Assignment\Models\Assignment;
use App\Modules\Education\ClassRoom\Enums\ClassStatus;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Models\ClassStudent;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Exam\Models\ExamResult;
use App\Modules\Education\Support\SummarizesByStatus;
use App\Modules\Education\Timetable\Models\Timetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class ClassService
{
    use HandlesEntityQueries;
    use SummarizesByStatus;

    /**
     * Paginated, searchable, filterable list (spec §2).
     */
    public function paginate(array $params = [])
    {
        $query = $this->baseQuery($params);

        $this->applySort($query, $params, ['code', 'name', 'start_date', 'status', 'created_at']);

        $result = $query
            ->withCount('enrollments')
            ->with(['course', 'teacher', 'assignee', 'timetables.rules', 'lessonPlan', 'room', 'business'])
            ->paginate($this->resolvePerPage($params));

        $this->attachCurrentStudents($result->getCollection());
        $this->attachAttendanceRate($result->getCollection());

        return $result;
    }

    /**
     * Aggregate counters for the list view, honouring the same filters/scope as
     * {@see paginate()}.
     *
     * @return array{total: int, by_status: array<string, int>, total_students: int}
     */
    public function summary(array $params = []): array
    {
        $byStatus = (clone $this->baseQuery($params))
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status');

        $classIds = (clone $this->baseQuery($params))->pluck('id');

        $totalStudents = ClassStudent::whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->distinct()
            ->count('student_id');

        return [
            'total' => $this->baseQuery($params)->count(),
            'by_status' => $this->countsByStatus($byStatus, ClassStatus::cases()),
            'total_students' => $totalStudents,
        ];
    }

    /**
     * The filtered, teacher-scoped base query shared by list and summary — no
     * sort, eager-loads or pagination applied.
     */
    private function baseQuery(array $params): Builder
    {
        $query = ClassRoom::query();

        // Search: name or code.
        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if (! empty($params['course_id'])) {
            $query->where('course_id', $params['course_id']);
        }

        if (! empty($params['lesson_plan_id'])) {
            $query->where('lesson_plan_id', $params['lesson_plan_id']);
        }

        if (! empty($params['teacher_id'])) {
            $query->where('teacher_id', $params['teacher_id']);
        }

        if (! empty($params['assignee_id'])) {
            $query->where('assignee_id', $params['assignee_id']);
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // Weekday filter: class must have a (non-cancelled) timetable rule on that weekday.
        if (! empty($params['weekday'])) {
            $query->whereHas(
                'timetables',
                fn ($q) => $q->where('status', '!=', Timetable::STATUS_CANCELLED)
                    ->whereHas('rules', fn ($q2) => $q2->where('day_of_week', $params['weekday'])),
            );
        }

        // Shift filter ("Ca học", spec §2): class must have a timetable rule starting
        // within the selected part of the day.
        if (! empty($params['shift'])) {
            $ranges = [
                'morning' => ['06:00:00', '11:59:59'],
                'afternoon' => ['12:00:00', '17:59:59'],
                'evening' => ['18:00:00', '23:59:59'],
            ];

            if (isset($ranges[$params['shift']])) {
                [$from, $to] = $ranges[$params['shift']];
                $query->whereHas(
                    'timetables',
                    fn ($q) => $q->where('status', '!=', Timetable::STATUS_CANCELLED)
                        ->whereHas('rules', fn ($q2) => $q2->whereBetween('start_time', [$from, $to])),
                );
            }
        }

        if (! empty($params['start_from'])) {
            $query->whereDate('start_date', '>=', $params['start_from']);
        }

        if (! empty($params['start_to'])) {
            $query->whereDate('start_date', '<=', $params['start_to']);
        }

        return $query;
    }

    public function find($id): ClassRoom
    {
        return ClassRoom::with(['course', 'teacher', 'assignee', 'timetables.rules', 'timetables.room', 'lessonPlan', 'room', 'business'])->findOrFail($id);
    }

    /**
     * Like find(), but decorated with the `current_students` count for the
     * capacity badge. Use this for read/response payloads — never for a model
     * that will be saved, as `current_students` is not a real column.
     */
    public function findWithStats($id): ClassRoom
    {
        $class = $this->find($id);

        $this->attachCurrentStudents(collect([$class]));

        return $class;
    }

    /**
     * Full detail with statistics (spec §7).
     */
    public function detail($id): array
    {
        $class = $this->findWithStats($id);

        return [
            'class' => $class,
            'statistics' => [
                'students' => $this->studentStats($id),
                'operational' => $this->operationalStats($id),
                'financial' => $this->financialStats($id),
            ],
        ];
    }

    /**
     * Create a class, optionally cloning the course curriculum (spec §3–5). A class
     * always starts without a schedule — that's created separately as a Timetable
     * (timetable-management.md), which is also what generates its sessions/lessons.
     */
    public function create(array $data): ClassRoom
    {
        return DB::transaction(function () use ($data) {
            $class = new ClassRoom($data);
            // A brand-new class can't have a Timetable yet, so `scheduled` classes
            // always start in draft (spec 009 §4) until one is created for them.
            $class->status = $this->computeStatus($class, false);
            $class->save();

            if (! empty($data['use_course_curriculum'])) {
                $this->copyCourseCurriculum($class);
            }

            return $this->findWithStats($class->id);
        });
    }

    /**
     * Update a class (spec §6). Cannot change course_id or re-clone curriculum
     * once sessions exist.
     */
    public function update($id, array $data): ClassRoom
    {
        return DB::transaction(function () use ($id, $data) {
            $class = $this->find($id);

            if ($this->hasSessions($id)) {
                unset($data['course_id'], $data['use_course_curriculum']);
            }

            unset($data['id'], $data['code'], $data['status']);

            $class->fill($data);

            // Recompute status only when not in a terminal/suspended state.
            if (! in_array($class->status, [ClassRoom::STATUS_SUSPENDED, ClassRoom::STATUS_COMPLETED])) {
                $class->status = $this->computeStatus($class, $this->hasSchedule($class->id));
            }

            $class->save();

            return $this->findWithStats($class->id);
        });
    }

    /**
     * Suspend a class (spec §9).
     *
     * @throws \RuntimeException
     */
    public function suspend($id, array $data): ClassRoom
    {
        $class = ClassRoom::findOrFail($id);

        if ($class->status === ClassRoom::STATUS_SUSPENDED) {
            throw new \RuntimeException('Lớp học đang ở trạng thái tạm ngừng.');
        }

        if ($class->status === ClassRoom::STATUS_COMPLETED) {
            throw new \RuntimeException('Lớp học đã kết thúc, không thể tạm ngừng.');
        }

        $class->update([
            'status' => ClassRoom::STATUS_SUSPENDED,
            'note' => $data['reason'] ?? null,
        ]);

        return $this->findWithStats($id);
    }

    /**
     * Restore a suspended class (spec §10).
     *
     * @throws \RuntimeException
     */
    public function restore($id): ClassRoom
    {
        $class = $this->find($id);

        if ($class->status !== ClassRoom::STATUS_SUSPENDED) {
            throw new \RuntimeException('Chỉ có thể khôi phục lớp học đang tạm ngừng.');
        }

        $status = $this->computeStatus($class, $this->hasSchedule($id));
        $class->update(['status' => $status]);

        return $this->findWithStats($id);
    }

    // ── Statistics ──────────────────────────────────────────────────────────

    public function studentStats($classId): array
    {
        $counts = $this->guard(fn () => ClassStudent::where('class_id', $classId)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status')
        );

        $counts = is_iterable($counts) ? collect($counts) : collect();

        return [
            'total' => (int) $counts->sum(),
            'active' => (int) ($counts['active'] ?? 0),
            'reserved' => (int) ($counts['reserved'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'dropped' => (int) ($counts['dropped'] ?? 0),
        ];
    }

    public function operationalStats($classId): array
    {
        $total = $this->guard(fn () => ClassSession::where('class_id', $classId)->count()
        );

        $completed = $this->guard(fn () => ClassSession::where('class_id', $classId)
            ->where('status', 'completed')
            ->count()
        );

        // Every lesson is either completed or pending, so pending is derivable.
        $pending = max(0, $total - $completed);

        $attendance = $this->guard(
            fn () => DB::table('edu_attendances')
                ->join('edu_sessions', 'edu_sessions.id', '=', 'edu_attendances.session_id')
                ->where('edu_sessions.class_id', $classId)
                ->whereNull('edu_attendances.deleted_at')
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN edu_attendances.status IN ('present', 'late') THEN 1 ELSE 0 END) as attended")
                ->first(),
            (object) ['total' => 0, 'attended' => 0],
        );

        $totalAttendance = (int) ($attendance->total ?? 0);
        $avgAttendanceRate = $totalAttendance > 0
            ? (int) round(((int) $attendance->attended / $totalAttendance) * 100)
            : 0;

        // Average exam score, and grade distribution, of the class's currently
        // active students.
        $examResultsQuery = fn () => DB::table('edu_exam_results')
            ->join('edu_class_students', 'edu_class_students.student_id', '=', 'edu_exam_results.student_id')
            ->where('edu_class_students.class_id', $classId)
            ->where('edu_class_students.status', 'active');

        $avgScore = $this->guard(fn () => $examResultsQuery()->avg('edu_exam_results.total_score'));

        $gradeCounts = $this->guard(
            fn () => $examResultsQuery()
                ->groupBy('edu_exam_results.grade')
                ->selectRaw('edu_exam_results.grade, COUNT(*) as aggregate')
                ->pluck('aggregate', 'grade'),
            collect(),
        );
        $gradeCounts = is_iterable($gradeCounts) ? collect($gradeCounts) : collect();
        $scoreDistribution = collect([ExamResult::GRADE_EXCELLENT, ExamResult::GRADE_GOOD, ExamResult::GRADE_PASS, ExamResult::GRADE_FAIL])
            ->map(fn ($grade) => ['grade' => $grade, 'count' => (int) ($gradeCounts[$grade] ?? 0)])
            ->all();

        $assignmentsCount = $this->guard(fn () => Assignment::where('class_room_id', $classId)->count()
        );

        $homework = $this->guard(
            fn () => DB::table('edu_assignment_submissions')
                ->join('edu_assignments', 'edu_assignments.id', '=', 'edu_assignment_submissions.assignment_id')
                ->where('edu_assignments.class_room_id', $classId)
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN edu_assignment_submissions.status IN ('submitted', 'late_submitted', 'graded', 'reviewed') THEN 1 ELSE 0 END) as completed")
                ->first(),
            (object) ['total' => 0, 'completed' => 0],
        );

        $homeworkTotal = (int) ($homework->total ?? 0);
        $homeworkCompletionRate = $homeworkTotal > 0
            ? (int) round(((int) $homework->completed / $homeworkTotal) * 100)
            : 0;

        return [
            'total_sessions' => $total,
            'completed_sessions' => $completed,
            'pending_sessions' => $pending,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'avg_attendance_rate' => $avgAttendanceRate,
            'avg_score' => $avgScore !== null ? round((float) $avgScore, 1) : null,
            'score_distribution' => $scoreDistribution,
            'assignments_count' => (int) $assignmentsCount,
            'homework_completion_rate' => $homeworkCompletionRate,
        ];
    }

    public function financialStats($classId): array
    {
        return [
            'total_revenue' => 0,
            'recognized_revenue' => 0,
            'debt' => 0,
            'refunds' => 0,
        ];
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Determine the correct status for a class based on its data and whether it
     * has a schedule. Status transitions driven by this method: draft → upcoming/active.
     * Suspend / complete are driven by explicit actions.
     */
    private function computeStatus(ClassRoom $class, bool $hasSchedule): string
    {
        if ($class->learning_type === 'scheduled') {
            if (! $hasSchedule || ! $class->teacher_id) {
                return ClassRoom::STATUS_DRAFT;
            }
        }

        $today = now()->toDateString();

        // `start_date` is cast to Carbon (midnight of that day), never a plain
        // string — comparing it against `$today` directly (Carbon > string)
        // does not compare as same-day-or-earlier the way it looks like it
        // should, so a class starting exactly today was wrongly kept
        // "upcoming" instead of flipping to "active". Compare date strings.
        return $class->start_date->toDateString() > $today
            ? ClassRoom::STATUS_UPCOMING
            : ClassRoom::STATUS_ACTIVE;
    }

    /** Whether the class has a live (non-cancelled) Timetable — its schedule (spec 030). */
    private function hasSchedule($classId): bool
    {
        return Timetable::where('class_room_id', $classId)
            ->where('status', '!=', Timetable::STATUS_CANCELLED)
            ->exists();
    }

    /**
     * Re-derive a class's status from its current schedule/teacher, unless it is
     * in a terminal/suspended state. Called by TimetableService after a timetable
     * is created or deleted.
     */
    public function recomputeStatus($classId): void
    {
        $class = $this->find($classId);

        if (in_array($class->status, [ClassRoom::STATUS_SUSPENDED, ClassRoom::STATUS_COMPLETED])) {
            return;
        }

        $status = $this->computeStatus($class, $this->hasSchedule($classId));
        $class->updateQuietly(['status' => $status]);
    }

    private function hasSessions($classId): bool
    {
        return $this->guard(fn () => ClassSession::where('class_id', $classId)->exists()
        );
    }

    /**
     * Copy course curriculum → class curriculum (spec §5).
     * Guarded: silently skips if the tables don't exist yet.
     */
    private function copyCourseCurriculum(ClassRoom $class): void
    {
        $this->guard(function () use ($class) {
            $curriculums = DB::table('edu_course_curriculums')
                ->where('course_id', $class->course_id)
                ->get();

            foreach ($curriculums as $c) {
                DB::table('edu_class_curriculums')->insert([
                    'class_id' => $class->id,
                    'course_curriculum_id' => $c->id,
                    'title' => $c->title,
                    'order' => $c->order ?? 0,
                    'content' => $c->content ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return true;
        });
    }

    /**
     * Attach the active-student count to each class as `current_students` so the
     * resource can derive the capacity warning badge (spec §8). Guarded: the
     * enrollment table may not exist yet, in which case every count defaults to 0.
     */
    private function attachCurrentStudents($classes): void
    {
        if ($classes->isEmpty()) {
            return;
        }

        $ids = $classes->pluck('id')->all();

        $counts = $this->guard(fn () => ClassStudent::whereIn('class_id', $ids)
            ->where('status', 'active')
            ->groupBy('class_id')
            ->selectRaw('class_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'class_id')
        );

        $counts = is_iterable($counts) ? collect($counts) : collect();

        foreach ($classes as $class) {
            $class->current_students = (int) ($counts[$class->id] ?? 0);
        }
    }

    /**
     * Attaches `avg_attendance_rate` ("Chuyên cần") to each class for the list
     * view, batched in a single query to avoid N+1 lookups per row.
     */
    private function attachAttendanceRate($classes): void
    {
        if ($classes->isEmpty()) {
            return;
        }

        $ids = $classes->pluck('id')->all();

        $rows = $this->guard(fn () => DB::table('edu_attendances')
            ->join('edu_sessions', 'edu_sessions.id', '=', 'edu_attendances.session_id')
            ->whereIn('edu_sessions.class_id', $ids)
            ->whereNull('edu_attendances.deleted_at')
            ->groupBy('edu_sessions.class_id')
            ->selectRaw("edu_sessions.class_id, COUNT(*) as total, SUM(CASE WHEN edu_attendances.status IN ('present', 'late') THEN 1 ELSE 0 END) as attended")
            ->get()
        );

        $rows = is_iterable($rows) ? collect($rows) : collect();
        $rates = $rows->mapWithKeys(function ($row) {
            $total = (int) $row->total;
            $rate = $total > 0 ? (int) round(((int) $row->attended / $total) * 100) : 0;

            return [$row->class_id => $rate];
        });

        foreach ($classes as $class) {
            $class->avg_attendance_rate = (int) ($rates[$class->id] ?? 0);
        }
    }
}
