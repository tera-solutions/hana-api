<?php

namespace App\Modules\Education\Support;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\LeaveRequest\Enums\LeaveRequestType;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
     *
     * Memoized on the current Request so the hr_teachers lookup (and the lazy
     * owned-class snapshot on the shared instance) runs once per request instead
     * of once per call site. Request attributes are the cache on purpose: they
     * die with the request, so long-running workers (queues, Octane) never leak
     * a scope across requests the way a static property would.
     */
    public static function current(): ?self
    {
        $user = Auth::guard('api')->user();

        if (! $user || $user->is_admin) {
            return null;
        }

        $attributes = app('request')->attributes;
        $key = 'education.teacher_scope.'.$user->id;

        if (! $attributes->has($key)) {
            $teacherId = Teacher::query()->where('user_id', $user->id)->value('id');

            $attributes->set($key, $teacherId ? new self((int) $teacherId, (int) $user->id) : null);
        }

        return $attributes->get($key);
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
                ->whereIn('edu_class_students.class_id', $classIds)
                ->whereNull('edu_class_students.deleted_at');
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
        $userId = $this->userId;

        $query->where(function (Builder $q) use ($classIds, $userId) {
            $q->whereIn('class_room_id', $classIds)
                ->orWhereExists(function ($sub) use ($classIds) {
                    $sub->selectRaw('1')
                        ->from('edu_lessons')
                        ->whereColumn('edu_lessons.id', 'edu_assignments.lesson_id')
                        ->whereIn('edu_lessons.class_room_id', $classIds);
                })
                // A freshly created assignment has neither class_room_id nor
                // lesson_id yet (assignment.md §VI: the class/level scope is set
                // via a follow-up update) — without this, the teacher can't see
                // their own draft to finish assigning it.
                ->orWhere('created_by', $userId);
        });
    }

    /**
     * Constrain a LessonPlan query. Lesson plans are per-course/level, not per-teacher,
     * so we scope to plans that are either attached to one of the teacher's classes
     * (`edu_classes.lesson_plan_id`) or share a course with one of those classes.
     *
     * Deliberately broad: curricula are shared per course, so every teacher on a
     * course sees — and, via {@see authorizeLessonPlan()}, may edit — that course's
     * plans, including drafts authored by a colleague.
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
     * Guard a lesson-plan write: owned when the plan is attached to one of the
     * teacher's classes, shares a course with one of them (same ownership test
     * as {@see constrainLessonPlans()}), or the teacher authored the plan
     * themselves — a teacher must be able to keep working a plan they just
     * created, before any class has been linked to it.
     *
     * @throws AuthorizationException
     */
    public function authorizeLessonPlan(int $planId, ?int $courseId, ?int $createdBy = null): void
    {
        $owned = in_array($planId, $this->lessonPlanIds(), true)
            || ($courseId !== null && in_array($courseId, $this->courseIds(), true))
            || ($createdBy !== null && $createdBy === $this->userId);

        if (! $owned) {
            throw new AuthorizationException('Bạn không có quyền truy cập giáo án này.');
        }
    }

    /**
     * Constrain an Exam query to exams that share a course with one of the
     * teacher's classes, or that the teacher authored themselves — same shape
     * as {@see constrainLessonPlans()}.
     */
    public function constrainExams(Builder $query): void
    {
        $courseIds = $this->courseIds();
        $userId = $this->userId;

        $query->where(function (Builder $q) use ($courseIds, $userId) {
            $q->where('created_by', $userId);

            if (! empty($courseIds)) {
                $q->orWhereIn('course_id', $courseIds);
            }
        });
    }

    /**
     * Guard an exam-template write: owned when the exam shares a course with
     * one of the teacher's classes, or the teacher authored it themselves —
     * same ownership test as {@see constrainExams()}, applied to a single id.
     *
     * @throws AuthorizationException
     */
    public function authorizeExam(int $examId, ?int $courseId, ?int $createdBy = null): void
    {
        $owned = ($courseId !== null && in_array($courseId, $this->courseIds(), true))
            || ($createdBy !== null && $createdBy === $this->userId);

        if (! $owned) {
            throw new AuthorizationException('Bạn không có quyền truy cập bài kiểm tra này.');
        }
    }

    /**
     * Guard a student write: throws 403 when the student is not enrolled in any
     * of the teacher's classes.
     *
     * @throws AuthorizationException
     */
    public function authorizeStudent(int $studentId): void
    {
        $owned = DB::table('edu_class_students')
            ->where('student_id', $studentId)
            ->whereIn('class_id', $this->classIds())
            ->whereNull('deleted_at')
            ->exists();

        if (! $owned) {
            throw new AuthorizationException('Bạn không có quyền truy cập học viên này.');
        }
    }

    /**
     * Constrain an ExamSession query: sessions of the teacher's classes, or where
     * the teacher is the session's invigilator. Sessions with no class_room_id
     * (e.g. standalone placement sittings) are only visible via the invigilator branch.
     */
    public function constrainExamSessions(Builder $query): void
    {
        $classIds = $this->classIds();

        $query->where(function (Builder $q) use ($classIds) {
            $q->whereIn('class_room_id', $classIds)
                ->orWhere('teacher_id', $this->teacherId);
        });
    }

    /**
     * Constrain a query joined to `edu_exam_sessions` (e.g. exam results/registrations,
     * which carry no class/teacher column of their own) to sessions owned per
     * {@see constrainExamSessions()}.
     */
    public function constrainByExamSession(Builder $query, string $sessionIdColumn): void
    {
        $classIds = $this->classIds();
        $teacherId = $this->teacherId;

        $query->whereExists(function ($sub) use ($classIds, $teacherId, $sessionIdColumn) {
            $sub->selectRaw('1')
                ->from('edu_exam_sessions')
                ->whereColumn('edu_exam_sessions.id', $sessionIdColumn)
                ->where(function ($q) use ($classIds, $teacherId) {
                    $q->whereIn('edu_exam_sessions.class_room_id', $classIds)
                        ->orWhere('edu_exam_sessions.teacher_id', $teacherId);
                });
        });
    }

    /**
     * Constrain a LeaveRequest query: the teacher's own leave requests, or a
     * student's leave request against one of the teacher's classes.
     */
    public function constrainLeaveRequests(Builder $query): void
    {
        $classIds = $this->classIds();
        $teacherId = $this->teacherId;

        $query->where(function (Builder $q) use ($classIds, $teacherId) {
            $q->where(function (Builder $qq) use ($teacherId) {
                $qq->where('requester_type', LeaveRequestType::TeacherLeave->requesterType())
                    ->where('requester_id', $teacherId);
            })->orWhere(function (Builder $qq) use ($classIds) {
                $qq->where('requester_type', LeaveRequestType::StudentLeave->requesterType())
                    ->whereIn('class_room_id', $classIds);
            });
        });
    }

    /**
     * Constrain a query whose given column references a student id (e.g. a
     * per-student record with no class_id of its own, such as edu_student_levels)
     * to students enrolled in the teacher's classes.
     */
    public function constrainByStudentId(Builder $query, string $studentIdColumn): void
    {
        $classIds = $this->classIds();

        $query->whereExists(function ($sub) use ($classIds, $studentIdColumn) {
            $sub->selectRaw('1')
                ->from('edu_class_students')
                ->whereColumn('edu_class_students.student_id', $studentIdColumn)
                ->whereIn('edu_class_students.class_id', $classIds)
                ->whereNull('edu_class_students.deleted_at');
        });
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
     * The instance (and via {@see current()} the whole request) sees the
     * ownership snapshot taken on first use — a mutation that reassigns a class
     * mid-request is not reflected until the next request.
     *
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
