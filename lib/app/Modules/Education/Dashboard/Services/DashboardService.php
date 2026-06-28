<?php

namespace App\Modules\Education\Dashboard\Services;

use App\Modules\Education\Assignment\Models\Assignment;
use App\Modules\Education\Assignment\Models\AssignmentSubmission;
use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Models\ClassStudent;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\LessonPlan\Models\LessonPlan;
use App\Modules\Education\Room\Models\Room;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregated teacher dashboard (dashboard_summary_backend_prompt.md). One request
 * replaces the ~6 separate /v1/edu/* calls the portal used to make.
 *
 * Everything is scoped to the authenticated teacher via {@see TeacherScope}; an admin
 * caller (no scope) sees the business-wide figures.
 *
 * Assumptions (documented per the prompt):
 *  - `edu_classes` has no level column, so `level` is always null.
 *  - lesson-plan `taught_percent` is a curriculum-completion proxy: completed sessions
 *    ÷ total sessions of the teacher's classes that use the plan (0 when none).
 *  - attendance is sourced from `edu_attendances` joined to the day's sessions; null
 *    when no attendance has been recorded for the date.
 */
class DashboardService
{
    public function summary(?string $date = null): array
    {
        $day = $date ? Carbon::parse($date) : Carbon::today();
        $today = $day->toDateString();
        $weekStart = $day->copy()->startOfWeek();   // Monday
        $weekEnd = $day->copy()->endOfWeek();       // Sunday

        $scope = TeacherScope::current();
        $classIds = $scope ? $scope->classIds() : ClassRoom::query()->pluck('id')->all();

        return [
            'stats' => $this->stats($classIds, $scope, $today),
            'schedule_today' => $this->scheduleToday($scope, $today),
            'schedule_week' => $this->scheduleWeek($scope, $weekStart, $weekEnd),
            'homework_pending' => $this->homeworkPending($classIds),
            'lesson_plans' => $this->lessonPlans($scope, $classIds),
            'my_classes' => $this->myClasses($classIds),
            'attendance' => $this->attendance($scope, $today),
        ];
    }

    /**
     * @param  array<int, int>  $classIds
     */
    private function stats(array $classIds, ?TeacherScope $scope, string $today): array
    {
        $studentsEnrolled = ClassStudent::whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->distinct()
            ->count('student_id');

        $activeClasses = ClassRoom::query()
            ->whereIn('id', $classIds)
            ->where('status', ClassRoom::STATUS_ACTIVE)
            ->count();

        $todaySessions = $this->scopedSessions($scope)
            ->whereDate('session_date', $today)
            ->where('status', '!=', ClassSession::STATUS_CANCELLED)
            ->get(['status']);

        $lessonsToday = $todaySessions->count();
        $done = $todaySessions->where('status', ClassSession::STATUS_COMPLETED)->count();

        return [
            'students_enrolled' => $studentsEnrolled,
            'active_classes' => $activeClasses,
            'lessons_today' => $lessonsToday,
            'completion_rate' => $lessonsToday > 0 ? (int) round($done / $lessonsToday * 100) : 0,
        ];
    }

    private function scheduleToday(?TeacherScope $scope, string $today): array
    {
        $sessions = $this->scopedSessions($scope)
            ->whereDate('session_date', $today)
            ->where('status', '!=', ClassSession::STATUS_CANCELLED)
            ->orderBy('start_time')
            ->get();

        if ($sessions->isEmpty()) {
            return [];
        }

        $classes = ClassRoom::query()
            ->whereIn('id', $sessions->pluck('class_id')->filter()->unique())
            ->get(['id', 'name', 'lesson_plan_id', 'room_id'])
            ->keyBy('id');

        $roomIds = $sessions->pluck('room_id')->merge($classes->pluck('room_id'))->filter()->unique();
        $rooms = Room::whereIn('id', $roomIds)->pluck('room_name', 'id');

        $studentCounts = $this->activeStudentCounts($classes->keys()->all());

        return $sessions->map(function (ClassSession $s) use ($classes, $rooms, $studentCounts) {
            $class = $classes->get($s->class_id);
            $roomId = $s->room_id ?? $class?->room_id;

            return [
                'id' => $s->id,
                'class_id' => $s->class_id,
                'class_name' => $class?->name,
                'level' => null,
                'room' => $roomId ? ($rooms[$roomId] ?? null) : null,
                'date' => $s->session_date?->toDateString(),
                'start_time' => $this->hhmm($s->start_time),
                'end_time' => $this->hhmm($s->end_time),
                'status' => $this->displayStatus($s->status),
                'lesson_plan_id' => $class?->lesson_plan_id,
                'student_count' => $studentCounts[$s->class_id] ?? 0,
            ];
        })->all();
    }

    /**
     * One entry per day of the ISO week (Mon→Sun, always 7), with the session count.
     */
    private function scheduleWeek(?TeacherScope $scope, Carbon $weekStart, Carbon $weekEnd): array
    {
        $byDate = $this->scopedSessions($scope)
            ->whereDate('session_date', '>=', $weekStart->toDateString())
            ->whereDate('session_date', '<=', $weekEnd->toDateString())
            ->where('status', '!=', ClassSession::STATUS_CANCELLED)
            ->get(['session_date'])
            ->groupBy(fn (ClassSession $s) => $s->session_date?->toDateString())
            ->map->count();

        $week = [];

        for ($d = $weekStart->copy(); $d->lte($weekEnd); $d->addDay()) {
            $ds = $d->toDateString();
            $week[] = ['date' => $ds, 'count' => (int) ($byDate[$ds] ?? 0)];
        }

        return $week;
    }

    /**
     * @param  array<int, int>  $classIds
     */
    private function homeworkPending(array $classIds): array
    {
        $pending = AssignmentSubmission::query()
            ->whereIn('status', [
                AssignmentSubmission::STATUS_SUBMITTED,
                AssignmentSubmission::STATUS_LATE_SUBMITTED,
            ])
            ->whereIn('assignment_id', Assignment::query()->whereIn('class_room_id', $classIds)->select('id'))
            ->selectRaw('assignment_id, COUNT(*) as pending_count')
            ->groupBy('assignment_id')
            ->pluck('pending_count', 'assignment_id');

        if ($pending->isEmpty()) {
            return [];
        }

        return Assignment::query()
            ->whereIn('id', $pending->keys())
            ->with('classRoom:id,name')
            ->orderBy('due_date')
            ->limit(5)
            ->get()
            ->map(fn (Assignment $a) => [
                'id' => $a->id,
                'title' => $a->assignment_name,
                'class_name' => $a->classRoom?->name,
                'pending_count' => (int) ($pending[$a->id] ?? 0),
                'deadline' => $a->due_date ? Carbon::parse($a->due_date)->toDateString() : null,
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $classIds
     */
    private function lessonPlans(?TeacherScope $scope, array $classIds): array
    {
        $query = LessonPlan::query()->with('course:id,name');

        if ($scope) {
            $scope->constrainLessonPlans($query);
        }

        return $query->orderByDesc('updated_at')
            ->limit(3)
            ->get()
            ->map(fn (LessonPlan $p) => [
                'id' => $p->id,
                'unit_name' => $p->plan_name,
                'class_name' => $p->course?->name,
                'taught_percent' => $this->taughtPercent($p->id, $classIds),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>  $classIds
     */
    private function myClasses(array $classIds): array
    {
        $classes = ClassRoom::query()
            ->whereIn('id', $classIds)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name']);

        if ($classes->isEmpty()) {
            return [];
        }

        $counts = $this->activeStudentCounts($classes->pluck('id')->all());

        return $classes->map(fn (ClassRoom $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'level' => null,
            'student_count' => $counts[$c->id] ?? 0,
        ])->all();
    }

    private function attendance(?TeacherScope $scope, string $today): ?array
    {
        $sessionIds = $this->scopedSessions($scope)
            ->whereDate('session_date', $today)
            ->pluck('id');

        if ($sessionIds->isEmpty()) {
            return null;
        }

        $counts = Attendance::whereIn('session_id', $sessionIds)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = (int) $counts->sum();

        if ($total === 0) {
            return null;
        }

        return [
            'present' => (int) ($counts['present'] ?? 0),
            'absent' => (int) ($counts['absent'] ?? 0),
            'late' => (int) ($counts['late'] ?? 0),
            'total' => $total,
        ];
    }

    /**
     * Completed ÷ total sessions of the teacher's classes that use the plan.
     *
     * @param  array<int, int>  $classIds
     */
    private function taughtPercent(int $planId, array $classIds): int
    {
        $planClassIds = ClassRoom::query()
            ->where('lesson_plan_id', $planId)
            ->whereIn('id', $classIds)
            ->pluck('id');

        if ($planClassIds->isEmpty()) {
            return 0;
        }

        $total = ClassSession::whereIn('class_id', $planClassIds)
            ->where('status', '!=', ClassSession::STATUS_CANCELLED)
            ->count();

        if ($total === 0) {
            return 0;
        }

        $done = ClassSession::whereIn('class_id', $planClassIds)
            ->where('status', ClassSession::STATUS_COMPLETED)
            ->count();

        return (int) round($done / $total * 100);
    }

    /**
     * Active-enrolment count per class id.
     *
     * @param  array<int, int>  $classIds
     * @return array<int, int>
     */
    private function activeStudentCounts(array $classIds): array
    {
        if (empty($classIds)) {
            return [];
        }

        return ClassStudent::whereIn('class_id', $classIds)
            ->where('status', 'active')
            ->groupBy('class_id')
            ->selectRaw('class_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'class_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function scopedSessions(?TeacherScope $scope): Builder
    {
        $query = ClassSession::query();

        if ($scope) {
            $scope->constrainSessions($query);
        }

        return $query;
    }

    private function hhmm(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }

    private function displayStatus(string $status): string
    {
        return $status === ClassSession::STATUS_COMPLETED ? 'done' : $status;
    }
}
