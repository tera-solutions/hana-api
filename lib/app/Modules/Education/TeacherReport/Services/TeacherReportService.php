<?php

namespace App\Modules\Education\TeacherReport\Services;

use App\Modules\Education\Assignment\Models\Assignment;
use App\Modules\Education\Assignment\Models\AssignmentSubmission;
use App\Modules\Education\Assignment\Models\AssignmentTarget;
use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Models\ClassStudent;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Evaluation\Models\Evaluation;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TeacherReportService
{
    /**
     * Teaching-activity summary for the authenticated teacher's own classes
     * (report.md — teacher app "Báo cáo" screen). No dedicated report tables;
     * everything is aggregated live from attendance/evaluation/session/assignment data.
     */
    public function summary(array $params = []): array
    {
        $teacherId = $this->actingTeacherId();

        $classIds = ClassRoom::where('teacher_id', $teacherId)
            ->when(! empty($params['class_id']), fn ($q) => $q->where('id', $params['class_id']))
            ->pluck('id');

        $dateFrom = $params['date_from'] ?? Carbon::now()->subMonth()->toDateString();
        $dateTo = $params['date_to'] ?? Carbon::now()->toDateString();

        $sessionsQuery = ClassSession::whereIn('class_id', $classIds)
            ->whereBetween('session_date', [$dateFrom, $dateTo]);
        $completedSessions = (clone $sessionsQuery)->where('status', ClassSession::STATUS_COMPLETED)->count();
        $sessionIds = (clone $sessionsQuery)->pluck('id');

        $totalStudents = ClassStudent::whereIn('class_id', $classIds)
            ->where('status', ClassStudent::STATUS_ACTIVE)
            ->distinct('student_id')
            ->count('student_id');

        $attendanceCounts = Attendance::whereIn('session_id', $sessionIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $attendanceTotal = (int) $attendanceCounts->sum();
        $presentCount = (int) ($attendanceCounts['present'] ?? 0) + (int) ($attendanceCounts['late'] ?? 0);
        $attendanceRate = $attendanceTotal > 0 ? round($presentCount / $attendanceTotal * 100, 1) : 0.0;

        $evaluationScores = Evaluation::whereIn('class_room_id', $classIds)
            ->where('evaluation_type', Evaluation::TYPE_STUDENT)
            ->whereNotNull('score')
            ->whereBetween('evaluated_at', [$dateFrom, $dateTo])
            ->pluck('score', 'id');
        $avgScore = $evaluationScores->count() > 0 ? round((float) $evaluationScores->avg(), 1) : 0.0;

        $assignmentIds = Assignment::whereIn('class_room_id', $classIds)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->pluck('id');
        $totalTargets = AssignmentTarget::whereIn('assignment_id', $assignmentIds)->count();
        $submissions = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)->get(['status']);
        $submittedCount = $submissions->whereIn('status', [
            AssignmentSubmission::STATUS_SUBMITTED,
            AssignmentSubmission::STATUS_LATE_SUBMITTED,
            AssignmentSubmission::STATUS_GRADED,
            AssignmentSubmission::STATUS_REVIEWED,
        ])->count();
        $gradedCount = $submissions->whereIn('status', [
            AssignmentSubmission::STATUS_GRADED,
            AssignmentSubmission::STATUS_REVIEWED,
        ])->count();
        $overdueCount = $submissions->where('status', AssignmentSubmission::STATUS_ASSIGNED)->count();
        $homeworkCompletionRate = $totalTargets > 0 ? round($submittedCount / $totalTargets * 100, 1) : 0.0;

        $teachingMinutes = ClassSession::whereIn('id', $sessionIds)
            ->where('status', ClassSession::STATUS_COMPLETED)
            ->get(['start_time', 'end_time'])
            ->sum(fn ($session) => $session->start_time && $session->end_time
                ? Carbon::parse($session->start_time)->diffInMinutes(Carbon::parse($session->end_time))
                : 0);

        return [
            'overview' => [
                'total_students' => $totalStudents,
                'total_sessions' => $completedSessions,
                'attendance_rate' => $attendanceRate,
                'avg_score' => $avgScore,
                'homework_completion_rate' => $homeworkCompletionRate,
                'teaching_minutes' => (int) $teachingMinutes,
            ],
            'score_by_class' => $this->scoreByClass($classIds, $dateFrom, $dateTo),
            'attendance_breakdown' => [
                'present' => (int) ($attendanceCounts['present'] ?? 0),
                'late' => (int) ($attendanceCounts['late'] ?? 0),
                'absent' => (int) ($attendanceCounts['absent'] ?? 0),
                'excused' => (int) ($attendanceCounts['excused'] ?? 0),
                'total' => $attendanceTotal,
            ],
            'score_distribution' => $this->scoreDistribution($evaluationScores->values()),
            'homework_status' => [
                'submitted' => $submittedCount,
                'pending' => max($totalTargets - $submittedCount, 0),
                'overdue' => $overdueCount,
                'total' => $totalTargets,
            ],
            'activity' => [
                'homework_assigned' => $assignmentIds->count(),
                'homework_submitted' => $submittedCount,
                'homework_graded' => $gradedCount,
                'materials_shared' => 0,
            ],
            'weekly_sessions' => $this->weeklySessions($classIds, $dateFrom, $dateTo),
        ];
    }

    private function scoreByClass($classIds, string $dateFrom, string $dateTo): array
    {
        return ClassRoom::whereIn('id', $classIds)
            ->get(['id', 'name'])
            ->map(function (ClassRoom $class) use ($dateFrom, $dateTo) {
                $avg = Evaluation::where('class_room_id', $class->id)
                    ->where('evaluation_type', Evaluation::TYPE_STUDENT)
                    ->whereNotNull('score')
                    ->whereBetween('evaluated_at', [$dateFrom, $dateTo])
                    ->avg('score');

                return [
                    'class_id' => $class->id,
                    'class_name' => $class->name,
                    'avg_score' => $avg !== null ? round((float) $avg, 1) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function scoreDistribution($scores): array
    {
        $buckets = ['excellent' => 0, 'good' => 0, 'average' => 0, 'weak' => 0];
        foreach ($scores as $score) {
            $value = (float) $score;
            if ($value >= 8.5) {
                $buckets['excellent']++;
            } elseif ($value >= 6.5) {
                $buckets['good']++;
            } elseif ($value >= 5.0) {
                $buckets['average']++;
            } else {
                $buckets['weak']++;
            }
        }

        return $buckets;
    }

    private function weeklySessions($classIds, string $dateFrom, string $dateTo): array
    {
        return ClassSession::whereIn('class_id', $classIds)
            ->where('status', ClassSession::STATUS_COMPLETED)
            ->whereBetween('session_date', [$dateFrom, $dateTo])
            ->orderBy('session_date')
            ->get(['session_date'])
            ->groupBy(fn ($session) => Carbon::parse($session->session_date)->startOfWeek()->toDateString())
            ->map(fn ($sessions, $weekStart) => [
                'week_start' => $weekStart,
                'total_sessions' => $sessions->count(),
            ])
            ->values()
            ->all();
    }

    private function actingTeacherId(): ?int
    {
        $user = Auth::guard('api')->user() ?? Auth::user();

        return Teacher::where('user_id', $user?->id)->value('id');
    }
}
