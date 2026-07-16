<?php

namespace App\Modules\Education\Room\Services;

use App\Modules\Education\ClassRoom\Enums\ClassStatus;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Models\ClassStudent;
use App\Modules\Education\ClassSession\Enums\ClassSessionStatus;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\Room\Models\Room;
use App\Modules\Education\Room\Models\RoomHistory;
use App\Modules\System\Branch\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class RoomService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable, sortable list (room.md §7).
     */
    public function paginate(array $params = [])
    {
        $query = Room::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('room_code', 'like', "%{$search}%")
                    ->orWhere('room_name', 'like', "%{$search}%");
            });
        }

        if (! empty($params['branch_id'])) {
            $query->where('branch_id', $params['branch_id']);
        }
        if (! empty($params['room_code'])) {
            $query->where('room_code', 'like', "%{$params['room_code']}%");
        }
        if (! empty($params['room_name'])) {
            $query->where('room_name', 'like', "%{$params['room_name']}%");
        }
        if (! empty($params['room_type'])) {
            $query->where('room_type', $params['room_type']);
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        if (! empty($params['floor'])) {
            $query->where('floor', $params['floor']);
        }

        $this->applySort($query, $params, ['room_code', 'room_name', 'capacity', 'floor', 'status', 'created_at']);

        // Note: `classes` here loads every active class per room (not just the
        // first) because a `limit()` inside an eager-load closure applies to
        // the whole batched query, not per-parent — RoomResource picks the
        // first one for the "Lớp học đang sử dụng" column.
        $paginator = $query
            ->with([
                'branch',
                'classes' => fn ($q) => $q->where('status', ClassStatus::Active->value)
                    ->withCount(['enrollments as enrollments_count' => fn ($eq) => $eq->where('status', 'active')]),
            ])
            ->withCount(['classes as active_classes_count' => fn ($q) => $q->where('status', ClassStatus::Active->value)])
            ->paginate($this->resolvePerPage($params));

        $this->attachNextSessions($paginator->getCollection());

        return $paginator;
    }

    /**
     * Aggregate counts for the room-list dashboard cards (room.md §5.1).
     */
    public function summary(array $params = []): array
    {
        return [
            'total' => Room::count(),
            'in_use' => $this->guard(fn () => Room::where('status', Room::STATUS_ACTIVE)
                ->whereHas('classes', fn ($q) => $q->where('status', ClassStatus::Active->value))
                ->count()
            ),
            'maintenance' => Room::where('status', Room::STATUS_MAINTENANCE)->count(),
            'empty' => $this->guard(fn () => Room::where('status', Room::STATUS_ACTIVE)
                ->whereDoesntHave('classes', fn ($q) => $q->where('status', ClassStatus::Active->value))
                ->count()
            ),
            'total_students' => $this->guard(fn () => ClassStudent::join('edu_classes', 'edu_class_students.class_id', '=', 'edu_classes.id')
                ->where('edu_classes.status', ClassStatus::Active->value)
                ->whereNotNull('edu_classes.room_id')
                ->where('edu_class_students.status', 'active')
                ->whereNull('edu_class_students.deleted_at')
                ->count()
            ),
            'online_rooms' => Room::where('room_type', 'online')->count(),
        ];
    }

    /**
     * Attach the nearest upcoming session/lesson to each room in the given
     * collection (room.md list §5.2 "Lịch học tiếp theo"). Batched per page to
     * avoid N+1 queries.
     */
    private function attachNextSessions($rooms): void
    {
        $ids = $rooms->pluck('id')->all();
        if (empty($ids)) {
            return;
        }

        $today = now()->toDateString();

        $sessions = $this->guardCollect(fn () => ClassSession::whereIn('room_id', $ids)
            ->whereNull('deleted_at')
            ->where('status', ClassSessionStatus::Upcoming->value)
            ->whereDate('session_date', '>=', $today)
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get(['room_id', 'session_date', 'start_time', 'end_time'])
            ->all()
        );

        $lessons = $this->guardCollect(fn () => Lesson::whereIn('room_id', $ids)
            ->whereIn('status', [Lesson::STATUS_SCHEDULED, Lesson::STATUS_CONFIRMED])
            ->whereDate('lesson_date', '>=', $today)
            ->orderBy('lesson_date')
            ->orderBy('start_time')
            ->get(['room_id', 'lesson_date', 'start_time', 'end_time'])
            ->all()
        );

        $candidatesByRoom = [];
        foreach ($sessions as $s) {
            $candidatesByRoom[$s->room_id][] = [
                'date' => $s->session_date,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
            ];
        }
        foreach ($lessons as $l) {
            $candidatesByRoom[$l->room_id][] = [
                'date' => $l->lesson_date,
                'start_time' => $l->start_time,
                'end_time' => $l->end_time,
            ];
        }

        foreach ($rooms as $room) {
            $candidates = $candidatesByRoom[$room->id] ?? [];
            usort($candidates, fn ($a, $b) => strcmp($a['date'].$a['start_time'], $b['date'].$b['start_time']));
            $room->setAttribute('next_session', $candidates[0] ?? null);
        }
    }

    public function find($id): Room
    {
        return Room::with('branch')
            ->withCount(['classes as active_classes_count' => fn ($q) => $q->where('status', ClassStatus::Active->value)])
            ->findOrFail($id);
    }

    /**
     * Detail with usage statistics and the classes currently using the room (room.md §8).
     */
    public function detail($id): array
    {
        return [
            'room' => $this->find($id),
            'statistics' => $this->usageStatistics($id),
            'classes_in_use' => $this->classesInUse($id),
            'current_session' => $this->currentSession($id),
        ];
    }

    /**
     * The room's in-progress session, if any (room-detail.md §6.1 "current_session").
     * Drives the session timer / student grid / materials on the room detail screen.
     */
    public function currentSession($id): ?array
    {
        $session = $this->guardValue(fn () => ClassSession::where('room_id', $id)
            ->where('status', ClassSessionStatus::Ongoing->value)
            ->whereNull('deleted_at')
            ->with([
                'classRoom' => fn ($q) => $q->with(['course', 'teacher', 'schedules'])
                    ->withCount(['enrollments as student_count' => $this->activeEnrollmentScope()]),
            ])
            ->orderByDesc('session_date')
            ->first()
        );

        if (! $session || ! $session->classRoom) {
            return null;
        }

        $classRoom = $session->classRoom;

        $levelName = $classRoom->course?->level_id
            ? $this->guardValue(fn () => DB::table('edu_levels')->where('id', $classRoom->course->level_id)->value('name'))
            : null;

        return [
            'session_id' => $session->id,
            'class_id' => $classRoom->id,
            'class_code' => $classRoom->code,
            'class_name' => $classRoom->name,
            'course_id' => $classRoom->course_id,
            'level' => $levelName,
            'teacher_name' => $classRoom->teacher?->full_name,
            'session_date' => $session->session_date,
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'class_start_date' => $classRoom->start_date,
            'class_end_date' => $classRoom->end_date,
            'student_count' => $classRoom->student_count ?? 0,
            'max_students' => $classRoom->max_capacity,
            'schedules' => $classRoom->schedules->map(fn ($s) => [
                'weekday' => $s->weekday,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
            ])->all(),
        ];
    }

    public function create(array $data): Room
    {
        return DB::transaction(function () use ($data) {
            $room = new Room($data);
            $room->status = Room::STATUS_ACTIVE; // room.md BR002
            // edu_rooms keeps the legacy (NOT NULL) business scope; derive it from the branch.
            $room->business_id = Branch::where('id', $data['branch_id'])->value('business_id');
            $room->save();

            $this->log($room, 'created', null, $room->status);

            return $this->find($room->id);
        });
    }

    public function update($id, array $data): Room
    {
        return DB::transaction(function () use ($id, $data) {
            $room = $this->find($id);

            unset($data['id'], $data['status'], $data['branch_id']);

            // room.md §6: code is immutable once the room is used by classes.
            if ($this->hasClasses($id)) {
                unset($data['room_code']);
            }

            // room.md BR003: capacity cannot drop below students in active classes.
            if (array_key_exists('capacity', $data)) {
                $minRequired = $this->maxStudentsInActiveClasses($id);
                if ((int) $data['capacity'] < $minRequired) {
                    throw new \RuntimeException("Không thể giảm sức chứa xuống dưới {$minRequired} (số học viên đang xếp trong lớp đang hoạt động).");
                }
            }

            $room->update($data);

            $this->log($room, 'updated');

            return $this->find($room->id);
        });
    }

    /**
     * Suspend a room (room.md §9).
     *
     * @throws \RuntimeException when a class is in progress (BR004) or when future
     *                           sessions exist and the caller has not confirmed (BR005).
     */
    public function suspend($id, array $data): Room
    {
        $room = $this->find($id);

        if ($room->status === Room::STATUS_INACTIVE) {
            throw new \RuntimeException('Phòng học đang ở trạng thái ngừng sử dụng.');
        }

        // BR004: a class is currently taking place in the room (session or lesson).
        if ($this->hasOngoingSession($id) || $this->hasOngoingLesson($id)) {
            throw new \RuntimeException('Không thể ngừng sử dụng: phòng đang có lớp học diễn ra.');
        }

        // BR005: warn when future sessions/lessons are scheduled unless confirmed.
        $future = $this->futureSessionCount($id) + $this->futureLessonCount($id);
        if ($future > 0 && empty($data['force'])) {
            throw new \RuntimeException("Phòng học đang được sử dụng cho {$future} buổi học trong tương lai. Bạn có chắc chắn muốn ngừng sử dụng?");
        }

        $from = $room->status;
        $room->update(['status' => Room::STATUS_INACTIVE]);

        $this->log($room, 'suspended', $from, Room::STATUS_INACTIVE, $data['reason'] ?? null);

        return $this->find($room->id);
    }

    /**
     * Restore a suspended room (room.md §10).
     *
     * @throws \RuntimeException when the room is already active.
     */
    public function restore($id, array $data = []): Room
    {
        $room = $this->find($id);

        if ($room->status === Room::STATUS_ACTIVE) {
            throw new \RuntimeException('Phòng học đang hoạt động.');
        }

        $from = $room->status;
        $room->update(['status' => Room::STATUS_ACTIVE]);

        $this->log($room, 'restored', $from, Room::STATUS_ACTIVE, $data['reason'] ?? null);

        return $this->find($room->id);
    }

    /**
     * Detect sessions overlapping the given slot in the room (room.md §11, BR006).
     *
     * @return array{has_conflict: bool, conflicts: array<int, array<string, mixed>>}
     */
    public function checkSchedule($id, array $params): array
    {
        $this->find($id); // 404 when the room does not exist.

        // Normalise H:i input to the H:i:s stored format so string comparison is exact.
        $start = substr($params['start_time'], 0, 5).':00';
        $end = substr($params['end_time'], 0, 5).':00';

        // Half-open overlap: existing.start < new.end AND existing.end > new.start.
        $sessionConflicts = $this->guardCollect(function () use ($id, $params, $start, $end) {
            $query = ClassSession::where('room_id', $id)
                ->whereNull('deleted_at')
                ->whereDate('session_date', $params['lesson_date'])
                ->where('status', '!=', ClassSessionStatus::Cancelled->value)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start);

            if (! empty($params['ignore_session_id'])) {
                $query->where('id', '!=', $params['ignore_session_id']);
            }

            return $query->get(['id', 'class_id', 'session_date', 'start_time', 'end_time'])
                ->map(fn ($s) => ['source' => 'session'] + (array) $s)
                ->all();
        });

        // edu_lessons (lesson.md §16) share the room; no deleted_at column here.
        $lessonConflicts = $this->guardCollect(function () use ($id, $params, $start, $end) {
            $query = Lesson::where('room_id', $id)
                ->whereDate('lesson_date', $params['lesson_date'])
                ->where('status', '!=', Lesson::STATUS_CANCELLED)
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start);

            if (! empty($params['ignore_lesson_id'])) {
                $query->where('id', '!=', $params['ignore_lesson_id']);
            }

            return $query->get(['id', 'class_room_id', 'lesson_date', 'start_time', 'end_time'])
                ->map(fn ($l) => [
                    'source' => 'lesson',
                    'id' => $l->id,
                    'class_id' => $l->class_room_id,
                    'session_date' => $l->lesson_date,
                    'start_time' => $l->start_time,
                    'end_time' => $l->end_time,
                ])
                ->all();
        });

        $conflicts = array_merge($sessionConflicts, $lessonConflicts);

        return [
            'has_conflict' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * Usage statistics (room.md §8). Computed from real data where the schema
     * allows; everything else returns 0.
     */
    public function usageStatistics($id): array
    {
        return [
            'total_classes' => $this->guard(fn () => ClassRoom::where('room_id', $id)->count()),
            'active_classes' => $this->guard(fn () => ClassRoom::where('room_id', $id)
                ->where('status', ClassStatus::Active->value)
                ->whereNull('deleted_at')
                ->count()
            ),
            'total_sessions' => $this->guard(fn () => ClassSession::where('room_id', $id)->count()),
            'completed_sessions' => $this->guard(fn () => ClassSession::where('room_id', $id)
                ->where('status', ClassSessionStatus::Completed->value)
                ->whereNull('deleted_at')
                ->count()
            ),
            'total_lessons' => $this->guard(fn () => Lesson::where('room_id', $id)
                ->count()
            ),
            'completed_lessons' => $this->guard(fn () => Lesson::where('room_id', $id)
                ->where('status', Lesson::STATUS_COMPLETED)
                ->count()
            ),
            'last_used_at' => $this->lastUsedAt($id),
        ];
    }

    /**
     * Classes currently using the room (room.md §8 "Lớp học đang sử dụng" tab).
     * Shape mirrors `currentSession()`'s class fields (`teacher_name`,
     * `student_count`, `max_students`) so both surface the same class summary.
     *
     * @return array<int, array<string, mixed>>
     */
    public function classesInUse($id): array
    {
        return $this->guardCollect(function () use ($id) {
            return ClassRoom::where('room_id', $id)
                ->where('status', ClassStatus::Active->value)
                ->whereNull('deleted_at')
                ->with('teacher')
                ->withCount(['enrollments as student_count' => $this->activeEnrollmentScope()])
                ->get(['id', 'code', 'name', 'teacher_id', 'max_capacity'])
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                    'teacher_id' => $c->teacher_id,
                    'teacher_name' => $c->teacher?->full_name,
                    'student_count' => $c->student_count ?? 0,
                    'max_students' => $c->max_capacity,
                ])
                ->all();
        });
    }

    /**
     * Shared "active enrollment" constraint used to count current class
     * headcount both in `classesInUse()` and `currentSession()`.
     */
    private function activeEnrollmentScope(): \Closure
    {
        return fn ($q) => $q->where('status', 'active')->whereNull('deleted_at');
    }

    private function hasClasses($id): bool
    {
        return $this->countLinked('edu_classes', $id, 'room_id') > 0;
    }

    private function hasOngoingSession($id): bool
    {
        return $this->guard(fn () => ClassSession::where('room_id', $id)
            ->where('status', ClassSessionStatus::Ongoing->value)
            ->whereNull('deleted_at')
            ->count()
        ) > 0;
    }

    private function futureSessionCount($id): int
    {
        return $this->guard(fn () => ClassSession::where('room_id', $id)
            ->where('status', ClassSessionStatus::Upcoming->value)
            ->whereDate('session_date', '>=', now()->toDateString())
            ->whereNull('deleted_at')
            ->count()
        );
    }

    private function hasOngoingLesson($id): bool
    {
        return $this->guard(fn () => Lesson::where('room_id', $id)
            ->where('status', Lesson::STATUS_IN_PROGRESS)
            ->count()
        ) > 0;
    }

    private function futureLessonCount($id): int
    {
        return $this->guard(fn () => Lesson::where('room_id', $id)
            ->whereIn('status', [Lesson::STATUS_SCHEDULED, Lesson::STATUS_CONFIRMED])
            ->whereDate('lesson_date', '>=', now()->toDateString())
            ->count()
        );
    }

    /**
     * Most recent completed usage across sessions (edu_sessions) and lessons
     * (edu_lessons); both store dates as Y-m-d so a string max is exact.
     */
    private function lastUsedAt($id): ?string
    {
        $session = $this->guardValue(fn () => ClassSession::where('room_id', $id)
            ->where('status', ClassSessionStatus::Completed->value)
            ->whereNull('deleted_at')
            ->max('session_date')
        );

        $lesson = $this->guardValue(fn () => Lesson::where('room_id', $id)
            ->where('status', Lesson::STATUS_COMPLETED)
            ->max('lesson_date')
        );

        $dates = array_filter([$session, $lesson]);

        return empty($dates) ? null : max($dates);
    }

    /**
     * Highest enrolled-student count across the active classes using this room.
     */
    private function maxStudentsInActiveClasses($id): int
    {
        return $this->guard(function () use ($id) {
            return (int) ClassStudent::join('edu_classes', 'edu_class_students.class_id', '=', 'edu_classes.id')
                ->where('edu_classes.room_id', $id)
                ->where('edu_classes.status', ClassStatus::Active->value)
                ->where('edu_class_students.status', 'active')
                ->whereNull('edu_class_students.deleted_at')
                ->selectRaw('COUNT(*) as c')
                ->groupBy('edu_class_students.class_id')
                ->orderByDesc('c')
                ->value('c') ?? 0;
        });
    }

    private function guardValue(callable $fn)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function guardCollect(callable $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function log(Room $room, string $action, $fromStatus = null, $toStatus = null, $reason = null, $note = null): void
    {
        RoomHistory::create([
            'room_id' => $room->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'note' => $note,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }
}
