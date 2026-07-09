<?php

namespace App\Modules\HR\Achievement\Services;

use App\Modules\Education\Support\TeacherScope;
use App\Modules\HR\Achievement\Models\TeacherReview;
use Illuminate\Support\Facades\DB;
use Package\Exception\AuthorizationException;

class AchievementService
{
    private const GOOD_RATING_THRESHOLD = 4;

    /**
     * Career totals + current overview for the authenticated teacher.
     */
    public function summary(): array
    {
        $scope = $this->requireScope();
        $classIds = $scope->classIds();

        $totalStudents = empty($classIds) ? 0 : DB::table('edu_class_students')
            ->whereIn('class_id', $classIds)
            ->whereNull('deleted_at')
            ->distinct('student_id')
            ->count('student_id');

        $totalHours = round((float) $this->completedSessions($scope->teacherId)
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, start_time, end_time)')) / 60, 1);

        $reviews = TeacherReview::query()->where('teacher_id', $scope->teacherId);
        $reviewCount = (clone $reviews)->count();
        $goodReviewCount = (clone $reviews)->where('rating', '>=', self::GOOD_RATING_THRESHOLD)->count();
        $avgRating = $reviewCount ? round((clone $reviews)->avg('rating'), 1) : 0;
        $satisfactionRate = $reviewCount ? round($goodReviewCount / $reviewCount * 100, 1) : 0;

        $sessionsThisMonth = $this->completedSessions($scope->teacherId)
            ->whereBetween('session_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $activeClasses = empty($classIds) ? 0 : DB::table('edu_classes')
            ->whereIn('id', $classIds)
            ->where('status', 'active')
            ->count();

        return [
            'career_stats' => [
                'total_classes' => count($classIds),
                'total_hours' => $totalHours,
                'total_students' => $totalStudents,
                'rating_rate' => $satisfactionRate,
            ],
            'overview' => [
                'avg_rating' => $avgRating,
                'satisfaction_rate' => $satisfactionRate,
                'sessions_count' => $sessionsThisMonth,
                'active_classes' => $activeClasses,
            ],
        ];
    }

    /**
     * Session/rating trend bucketed by week, month or year.
     */
    public function progress(string $period = 'month'): array
    {
        $scope = $this->requireScope();
        $format = match ($period) {
            'week' => '%x-W%v',
            'year' => '%Y',
            default => '%Y-%m',
        };

        $sessionRows = $this->completedSessions($scope->teacherId)
            ->selectRaw("DATE_FORMAT(session_date, '{$format}') as bucket, COUNT(*) as sessions, GROUP_CONCAT(id) as session_ids")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $reviewRows = TeacherReview::query()
            ->where('teacher_id', $scope->teacherId)
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as bucket, AVG(rating) as avg_rating")
            ->groupBy('bucket')
            ->pluck('avg_rating', 'bucket');

        $chartData = $sessionRows->map(function ($row) use ($reviewRows) {
            $sessionIds = $row->session_ids ? array_map('intval', explode(',', $row->session_ids)) : [];
            $students = empty($sessionIds) ? 0 : DB::table('edu_attendances')
                ->whereIn('session_id', $sessionIds)
                ->whereNull('deleted_at')
                ->distinct('student_id')
                ->count('student_id');

            return [
                'label' => $row->bucket,
                'rating' => isset($reviewRows[$row->bucket]) ? round((float) $reviewRows[$row->bucket], 1) : null,
                'students' => $students,
                'sessions' => (int) $row->sessions,
            ];
        })->values();

        return ['chart_data' => $chartData];
    }

    /**
     * The teacher's own reviews, most recent first.
     */
    public function reviews(array $params = [])
    {
        $scope = $this->requireScope();
        $perPage = min((int) ($params['per_page'] ?? 20), 100) ?: 20;

        return TeacherReview::query()
            ->with(['student'])
            ->where('teacher_id', $scope->teacherId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function createReview(array $data): TeacherReview
    {
        return TeacherReview::create([
            'teacher_id' => $data['teacher_id'],
            'student_id' => $data['student_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'rating' => $data['rating'],
            'content' => $data['content'] ?? null,
        ]);
    }

    private function completedSessions(int $teacherId)
    {
        return DB::table('edu_sessions')
            ->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)
                    ->orWhere('substitute_teacher_id', $teacherId);
            })
            ->where('status', 'completed')
            ->whereNull('deleted_at');
    }

    private function requireScope(): TeacherScope
    {
        $scope = TeacherScope::current();

        if (! $scope) {
            throw new AuthorizationException('Chỉ giáo viên mới có thể xem thành tích của mình.');
        }

        return $scope;
    }
}
