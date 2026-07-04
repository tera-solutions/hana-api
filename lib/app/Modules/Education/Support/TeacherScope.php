<?php

namespace App\Modules\Education\Support;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Package\Exception\AuthorizationException;

/**
 * Conditional teacher scoping for the shared /v1/edu/* endpoints.
 *
 * The Admin/back-office portal and the Teacher portal consume the same endpoints.
 * When the caller is a teacher, every list/detail/calendar response is constrained
 * to the classes that teacher owns; admins and other staff keep the unscoped view.
 *
 * Who is "a teacher": a non-admin user that has a row in `hr_teachers`
 * (`hr_teachers.user_id = users.id`). Admins (`users.is_admin`) are never scoped.
 *
 * Class ownership: a class belongs to the teacher when any of the following hold —
 *  - it is the class's primary teacher (`edu_classes.teacher_id`),
 *  - the teacher's user is the class assignee (`edu_classes.assignee_id`),
 *  - the teacher is a co-teacher/assistant (`edu_class_teacher.teacher_id`).
 *
 * Resolve the scope with {@see TeacherScope::current()} — it returns null when the
 * caller must not be scoped, so callers apply the constraints only when non-null.
 */
class TeacherScope
{
    /**
     * Owned-class rows (id, course_id, lesson_plan_id), resolved lazily once.
     *
     * @var array<int, object>|null
     */
    private ?array $classes = null;

    private function __construct(
        public readonly int $teacherId,
        public readonly int $userId,
    ) {}

    /**
     * The scope for the authenticated caller, or null when no scoping applies
     * (unauthenticated, admin, or a non-teacher staff user).
     */
    public static function current(): ?self
    {
        $user = Auth::guard('api')->user();

        if (! $user || $user->is_admin) {
            return null;
        }

        $teacherId = Teacher::query()->where('user_id', $user->id)->value('id');

        if (! $teacherId) {
            return null;
        }

        return new self((int) $teacherId, (int) $user->id);
    }

    /**
     * IDs of the classes this teacher owns. Empty when they own none, which makes
     * every constraint below yield an empty (not erroring) result set.
     *
     * @return array<int, int>
     */
    public function classIds(): array
    {
        return array_map(static fn ($c) => (int) $c->id, $this->classes());
    }

    public function ownsClass(int $classId): bool
    {
        return in_array($classId, $this->classIds(), true);
    }

    /**
     * Constrain a ClassRoom query to the teacher's own classes.
     */
    public function constrainClasses(Builder $query, string $idColumn = 'id'): void
    {
        $query->whereIn($idColumn, $this->classIds());
    }

    /**
     * Constrain a query whose given column references a class id (e.g. lessons,
     * attendance via session) to the teacher's own classes.
     */
    public function constrainByClass(Builder $query, string $column): void
    {
        $query->whereIn($column, $this->classIds());
    }

    /**
     * Constrain a ClassSession query: sessions of the teacher's classes, or where
     * the teacher is the session's (substitute) teacher.
     */
    public function constrainSessions(Builder $query): void
    {
        $classIds = $this->classIds();

        $query->where(function (Builder $q) use ($classIds) {
            $q->whereIn('class_id', $classIds)
                ->orWhere('teacher_id', $this->teacherId)
                ->orWhere('substitute_teacher_id', $this->teacherId);
        });
    }

    /**
     * Constrain a Student query to students enrolled in the teacher's classes.
     */
    public function constrainStudents(Builder $query): void
    {
        $classIds = $this->classIds();

        $query->whereExists(function ($sub) use ($classIds) {
            $sub->selectRaw('1')
                ->from('edu_class_students')
                ->whereColumn('edu_class_students.student_id', 'edu_students.id')
                ->whereIn('edu_class_students.class_id', $classIds);
        });
    }

    /**
     * Constrain an Assignment query to assignments of the teacher's classes, or of
     * lessons belonging to those classes. Course-only assignments (no class/lesson)
     * are not visible to teachers.
     */
    public function constrainAssignments(Builder $query): void
    {
        $classIds = $this->classIds();

        $query->where(function (Builder $q) use ($classIds) {
            $q->whereIn('class_room_id', $classIds)
                ->orWhereExists(function ($sub) use ($classIds) {
                    $sub->selectRaw('1')
                        ->from('edu_lessons')
                        ->whereColumn('edu_lessons.id', 'edu_assignments.lesson_id')
                        ->whereIn('edu_lessons.class_room_id', $classIds);
                });
        });
    }

    /**
     * Constrain a LessonPlan query. Lesson plans are per-course/level, not per-teacher,
     * so we scope to plans that are either attached to one of the teacher's classes
     * (`edu_classes.lesson_plan_id`) or share a course with one of those classes.
     */
    public function constrainLessonPlans(Builder $query): void
    {
        $planIds = $this->lessonPlanIds();
        $courseIds = $this->courseIds();

        $query->where(function (Builder $q) use ($planIds, $courseIds) {
            $q->whereIn('id', $planIds);

            if (! empty($courseIds)) {
                $q->orWhereIn('course_id', $courseIds);
            }
        });
    }

    /**
     * Guard a class-detail read: throws 403 when the class is not the teacher's.
     *
     * @throws AuthorizationException
     */
    public function authorizeClass(int $classId): void
    {
        if (! $this->ownsClass($classId)) {
            throw new AuthorizationException('Bạn không có quyền truy cập lớp học này.');
        }
    }

    /**
     * Guard a session-detail read: owned when the session's class is the teacher's,
     * or the teacher is its (substitute) teacher.
     *
     * @throws AuthorizationException
     */
    public function authorizeSession(ClassSession $session): void
    {
        $owned = $this->ownsClass((int) $session->class_id)
            || (int) $session->teacher_id === $this->teacherId
            || (int) $session->substitute_teacher_id === $this->teacherId;

        if (! $owned) {
            throw new AuthorizationException('Bạn không có quyền truy cập buổi học này.');
        }
    }

    /**
     * @return array<int, object>
     */
    private function classes(): array
    {
        return $this->classes ??= ClassRoom::query()
            ->where(function (Builder $q) {
                $q->where('teacher_id', $this->teacherId)
                    ->orWhere('assignee_id', $this->userId)
                    ->orWhereExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('edu_class_teacher')
                            ->whereColumn('edu_class_teacher.class_id', 'edu_classes.id')
                            ->where('edu_class_teacher.teacher_id', $this->teacherId);
                    });
            })
            ->get(['id', 'course_id', 'lesson_plan_id'])
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function courseIds(): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn ($c) => $c->course_id ? (int) $c->course_id : null, $this->classes())
        )));
    }

    /**
     * @return array<int, int>
     */
    private function lessonPlanIds(): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn ($c) => $c->lesson_plan_id ? (int) $c->lesson_plan_id : null, $this->classes())
        )));
    }
}
