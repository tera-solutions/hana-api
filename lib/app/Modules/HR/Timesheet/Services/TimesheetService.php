<?php

namespace App\Modules\HR\Timesheet\Services;

use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\ClassSessionFeedback\Models\ClassSessionFeedback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * A teacher's "công" (timesheet): read-only, derived entirely from `ClassSession` +
 * `Attendance` — no separate check-in/out table (project decision 2026-07-17). A
 * session counts as worked once the teacher has taken attendance for it; hours
 * come from the session's own start/end time, not a self-reported duration.
 */
class TimesheetService
{
    use HandlesEntityQueries;

    /**
     * Sessions with attendance recorded, richest-first fields for the table +
     * cards: computed hours, the owning class's `learning_type`, and this
     * session's attendance/rating stats.
     */
    public function sessions(int $teacherId, array $params = [])
    {
        $query = $this->workedSessionQuery($teacherId, $params)
            ->with(['classRoom', 'room'])
            ->withCount([
                'attendances as present_count' => fn (Builder $q) => $q->where('status', Attendance::STATUS_PRESENT),
                'attendances as absent_count' => fn (Builder $q) => $q->where('status', Attendance::STATUS_ABSENT),
                'attendances',
            ])
            ->withAvg('feedbacks', 'rating');

        $this->applySort($query, $params, ['session_date', 'start_time']);
        if (empty($params['sort_by'])) {
            $query->orderByDesc('session_date')->orderByDesc('start_time');
        }

        return $query->paginate($this->resolvePerPage($params));
    }

    /**
     * Aggregate stat cards: total hours, hours by class `learning_type`,
     * attendance rate across worked sessions, average session rating.
     */
    public function summary(int $teacherId, array $params = []): array
    {
        $sessions = $this->workedSessionQuery($teacherId, $params)->with('classRoom')->get();

        $totalHours = 0.0;
        $hoursByType = [];
        foreach ($sessions as $session) {
            $hours = $this->sessionHours($session);
            $totalHours += $hours;

            $type = $session->classRoom?->learning_type ?? 'unspecified';
            $hoursByType[$type] = ($hoursByType[$type] ?? 0) + $hours;
        }

        $sessionIds = $sessions->pluck('id');

        $attendanceCounts = Attendance::whereIn('session_id', $sessionIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalAttendance = (int) $attendanceCounts->sum();
        $presentAttendance = (int) ($attendanceCounts[Attendance::STATUS_PRESENT] ?? 0);

        $averageRating = ClassSessionFeedback::whereIn('session_id', $sessionIds)->avg('rating');

        return [
            'total_sessions' => $sessions->count(),
            'total_hours' => round($totalHours, 2),
            'hours_by_type' => array_map(fn ($h) => round($h, 2), $hoursByType),
            'attendance_rate' => $totalAttendance > 0 ? round($presentAttendance / $totalAttendance * 100, 1) : null,
            'average_rating' => $averageRating !== null ? round((float) $averageRating, 2) : null,
        ];
    }

    /**
     * Sessions where the teacher (as primary OR substitute) has taken attendance —
     * the operational definition of "công" agreed 2026-07-17: no check-in/out step,
     * attendance itself is the signal the session was actually taught.
     */
    private function workedSessionQuery(int $teacherId, array $params): Builder
    {
        $query = ClassSession::query()
            ->where(function (Builder $q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->orWhere('substitute_teacher_id', $teacherId);
            })
            ->whereHas('attendances');

        if (! empty($params['date_from'])) {
            $query->whereDate('session_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('session_date', '<=', $params['date_to']);
        }
        if (! empty($params['month'])) {
            $anchor = Carbon::parse($params['month'].'-01');
            $query->whereBetween('session_date', [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()]);
        }

        return $query;
    }

    /** Shared with `PayrollService` (per-class income breakdown uses the same hours math). */
    public function sessionHours(ClassSession $session): float
    {
        if (! $session->start_time || ! $session->end_time) {
            return 0.0;
        }

        return abs(Carbon::parse($session->start_time)->diffInMinutes(Carbon::parse($session->end_time))) / 60;
    }
}
