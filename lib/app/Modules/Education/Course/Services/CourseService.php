<?php

namespace App\Modules\Education\Course\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\Course\Events\CourseCreated;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Course\Models\CourseHistory;
use App\Modules\Education\Enrollment\Models\Enrollment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class CourseService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list.
     */
    public function paginate(array $params = [])
    {
        $query = Course::query();

        // Search: name, code.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Status tab: is_active=true|false, or status=active|inactive.
        if (array_key_exists('is_active', $params) && $params['is_active'] !== '' && $params['is_active'] !== null) {
            $query->where('is_active', filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN));
        } elseif (! empty($params['status'])) {
            $query->where('is_active', $params['status'] === 'active');
        }

        // Duration range (minutes).
        if (! empty($params['duration_min'])) {
            $query->where('duration_minutes', '>=', (int) $params['duration_min']);
        }
        if (! empty($params['duration_max'])) {
            $query->where('duration_minutes', '<=', (int) $params['duration_max']);
        }

        // Price-per-lesson range.
        if (! empty($params['price_min'])) {
            $query->where('price_per_lesson', '>=', $params['price_min']);
        }
        if (! empty($params['price_max'])) {
            $query->where('price_per_lesson', '<=', $params['price_max']);
        }

        if (! empty($params['created_by'])) {
            $query->where('created_by', $params['created_by']);
        }
        if (! empty($params['created_from'])) {
            $query->whereDate('created_at', '>=', $params['created_from']);
        }
        if (! empty($params['created_to'])) {
            $query->whereDate('created_at', '<=', $params['created_to']);
        }

        $this->applySort($query, $params, ['code', 'name', 'duration_minutes', 'price_per_lesson', 'created_at']);

        return $query->with('business')->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return Course::with('business', 'curriculums')->findOrFail($id);
    }

    /**
     * Detail with the operational / financial / rating statistics.
     */
    public function detail($id): array
    {
        return [
            'course' => $this->find($id),
            'statistics' => [
                'operational' => $this->operationalStatistics($id),
                'financial' => $this->financialSummary($id),
                'rating' => $this->ratingSummary($id),
            ],
        ];
    }

    /**
     * Operational metrics (classes / students). Computed from real data where the
     * schema allows; everything else returns 0.
     */
    public function operationalStatistics($id): array
    {
        return [
            'total_classes' => $this->countLinked('edu_classes', $id, 'course_id'),
            'active_classes' => $this->guard(fn () => ClassRoom::where('course_id', $id)
                ->whereIn('status', ['opening', 'running'])
                ->count()
            ),
            'total_students' => $this->countStudents($id),
            'studying_students' => $this->countStudents($id, 'studying'),
            'reserved_students' => 0, // not modelled on enrollments yet
            'completed_students' => $this->countStudents($id, 'completed'),
        ];
    }

    /**
     * Financial summary. Finance tables are not yet linked to courses, so these
     * are structural placeholders (0) per course.md §2 business rules.
     */
    public function financialSummary($id): array
    {
        return [
            'revenue_sales' => 0,
            'recognized_revenue' => 0,
            'refunds' => 0,
            'debt' => 0,
            'balance' => 0,
        ];
    }

    /**
     * Rating summary. No course-review table yet, so placeholders.
     */
    public function ratingSummary($id): array
    {
        return [
            'average_rating' => 0,
            'total_reviews' => 0,
            'satisfaction_rate' => 0,
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $course = new Course($data);
            $course->is_active = $data['is_active'] ?? true;
            $course->save();

            $this->log($course, 'created', null, $course->is_active);

            event(new CourseCreated($course));

            return $this->find($course->id);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $course = $this->find($id);

            unset($data['id'], $data['is_active']);

            // course.md §4: code is immutable once classes have been created.
            if ($this->hasClasses($id)) {
                unset($data['code']);
            }

            $course->update($data);

            $this->log($course, 'updated');

            return $this->find($course->id);
        });
    }

    /**
     * Deactivate a course (no new classes / enrolments; existing classes keep running).
     *
     * @throws \RuntimeException when the course is already inactive.
     */
    public function suspend($id, array $data)
    {
        $course = $this->find($id);

        if (! $course->is_active) {
            throw new \RuntimeException('Khóa học đang ở trạng thái ngừng hoạt động.');
        }

        $course->update(['is_active' => false]);

        $this->log($course, 'suspended', true, false, $data['reason'] ?? null);

        return $this->find($course->id);
    }

    /**
     * Reactivate a suspended course.
     *
     * @throws \RuntimeException when the course is already active.
     */
    public function restore($id, array $data = [])
    {
        $course = $this->find($id);

        if ($course->is_active) {
            throw new \RuntimeException('Khóa học đang hoạt động.');
        }

        $course->update(['is_active' => true]);

        $this->log($course, 'restored', false, true, $data['reason'] ?? null);

        return $this->find($course->id);
    }

    private function hasClasses($id): bool
    {
        return $this->countLinked('edu_classes', $id, 'course_id') > 0;
    }

    /**
     * Distinct students enrolled in this course's classes, optionally by enrolment status.
     */
    private function countStudents($id, ?string $status = null): int
    {
        return $this->guard(function () use ($id, $status) {
            $query = Enrollment::join('edu_classes', 'edu_enrollments.class_id', '=', 'edu_classes.id')
                ->where('edu_classes.course_id', $id);

            if ($status !== null) {
                $query->where('edu_enrollments.status', $status);
            }

            return $query->distinct('edu_enrollments.student_id')->count('edu_enrollments.student_id');
        });
    }

    private function log(Course $course, string $action, $fromActive = null, $toActive = null, $reason = null, $note = null): void
    {
        CourseHistory::create([
            'business_id' => $course->business_id,
            'course_id' => $course->id,
            'action' => $action,
            'from_active' => $fromActive,
            'to_active' => $toActive,
            'reason' => $reason,
            'note' => $note,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }
}
