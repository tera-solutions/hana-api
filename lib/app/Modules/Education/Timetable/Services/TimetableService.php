<?php

namespace App\Modules\Education\Timetable\Services;

use App\Modules\Education\ClassRoom\Models\ClassStudent;
use App\Modules\Education\ClassRoom\Services\ClassService;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\ClassSession\Services\ClassSessionService;
use App\Modules\Education\Room\Models\Room;
use App\Modules\Education\Timetable\Models\Timetable;
use App\Modules\Education\Timetable\Models\TimetableChange;
use App\Modules\Education\Timetable\Models\TimetableRule;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * Timetable business logic (timetable-management.md). Creating a timetable generates the
 * class sessions from its rules, guarding against room/teacher clashes (BR-01/BR-02) and
 * room capacity (BR-03); each session belongs to exactly one timetable (BR-07).
 */
class TimetableService
{
    use HandlesEntityQueries;

    public function __construct(
        private ClassSessionService $sessions,
        private ClassService $classes,
    ) {}

    public function paginate(array $params = [])
    {
        $query = Timetable::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('timetable_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        foreach (['class_room_id', 'teacher_id', 'room_id', 'course_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['timetable_code', 'start_date', 'end_date', 'status', 'created_at']);

        return $query->with(['course', 'classRoom'])->paginate($this->resolvePerPage($params));
    }

    public function find($id): Timetable
    {
        $timetable = Timetable::with(['course', 'classRoom', 'rules', 'sessions' => fn ($q) => $q->orderBy('session_date')->orderBy('start_time')])
            ->findOrFail($id);

        return $timetable;
    }

    /**
     * @throws \RuntimeException
     */
    public function create(array $data): Timetable
    {
        return DB::transaction(function () use ($data) {
            $plan = $this->planSessions($data);

            if ($plan === []) {
                throw new \RuntimeException('Cấu hình lịch không sinh được buổi học nào.');
            }

            $this->assertCapacity($data);
            $this->assertNoConflicts($plan, $data);

            $timetable = new Timetable(Arr::except($data, ['rules', 'dates']));
            $timetable->timetable_code = $this->generateCode();
            $timetable->status = $data['status'] ?? Timetable::STATUS_DRAFT;
            $timetable->total_sessions = count($plan);
            $timetable->save();

            foreach ($data['rules'] ?? [] as $rule) {
                TimetableRule::create([
                    'timetable_id' => $timetable->id,
                    'day_of_week' => $rule['day_of_week'],
                    'start_time' => $this->time($rule['start_time']),
                    'end_time' => $this->time($rule['end_time']),
                ]);
            }

            $this->generateSessions($timetable, $plan, $data);

            // A class only leaves DRAFT once it has a real schedule (spec 009 §4);
            // that schedule is now a Timetable instead of a ClassSchedule.
            $this->classes->recomputeStatus($timetable->class_room_id);

            return $this->find($timetable->id);
        });
    }

    public function update($id, array $data): Timetable
    {
        return DB::transaction(function () use ($id, $data) {
            $timetable = $this->find($id);
            $timetable->update($data);

            return $this->find($id);
        });
    }

    public function delete($id): void
    {
        $timetable = $this->find($id);
        $classId = $timetable->class_room_id;
        $timetable->delete();

        $this->classes->recomputeStatus($classId);
    }

    /**
     * Assign a different teacher to a session that belongs to a timetable (BR-04/BR-05):
     * blocks completed sessions, delegates the actual write + conflict-check to
     * ClassSessionService, and records the change in edu_timetable_changes.
     *
     * @throws \RuntimeException
     */
    public function changeTeacher(int $sessionId, array $data): ClassSession
    {
        return DB::transaction(function () use ($sessionId, $data) {
            $session = $this->guardSessionEditable($sessionId);
            $oldTeacherId = $session->teacher_id;

            $updated = $this->sessions->update($sessionId, ['teacher_id' => $data['teacher_id']]);

            $this->recordChange(
                $session,
                'teacher_change',
                $this->teacherLabel($oldTeacherId),
                $this->teacherLabel($data['teacher_id']),
                $data['reason'] ?? null,
            );

            return $updated;
        });
    }

    /**
     * Assign a different room to a session that belongs to a timetable (BR-04/BR-05).
     *
     * @throws \RuntimeException
     */
    public function changeRoom(int $sessionId, array $data): ClassSession
    {
        return DB::transaction(function () use ($sessionId, $data) {
            $session = $this->guardSessionEditable($sessionId);
            $oldRoomId = $session->room_id;

            $updated = $this->sessions->update($sessionId, ['room_id' => $data['room_id']]);

            $this->recordChange(
                $session,
                'room_change',
                $this->roomLabel($oldRoomId),
                $this->roomLabel($data['room_id']),
                $data['reason'] ?? null,
            );

            return $updated;
        });
    }

    /**
     * Move a session to a different date/time (BR-04/BR-06). Attendance/Lesson records
     * follow the session's date/time by reference (same row), so they stay in sync
     * automatically; there is no separate copy of the schedule to update.
     *
     * @throws \RuntimeException
     */
    public function reschedule(int $sessionId, array $data): ClassSession
    {
        return DB::transaction(function () use ($sessionId, $data) {
            $session = $this->guardSessionEditable($sessionId);
            $old = sprintf('%s %s-%s', $session->session_date->toDateString(), $session->start_time, $session->end_time);

            // Pass the session's current teacher/room through explicitly — ClassSessionService's
            // conflict check only guards teacher/room clashes when those keys are present in
            // $data, and a bare date/time move must still catch a clash at the new slot (BR-01/02).
            $updated = $this->sessions->update($sessionId, [
                'session_date' => $data['session_date'],
                'start_time' => $this->time($data['start_time']),
                'end_time' => $this->time($data['end_time']),
                'teacher_id' => $session->teacher_id,
                'room_id' => $session->room_id,
            ]);

            $new = sprintf('%s %s-%s', $data['session_date'], $this->time($data['start_time']), $this->time($data['end_time']));

            $this->recordChange($session, 'reschedule', $old, $new, $data['reason'] ?? null);

            return $updated;
        });
    }

    /**
     * Cancel a session that belongs to a timetable (BR-04/BR-05).
     *
     * @throws \RuntimeException
     */
    public function cancelSession(int $sessionId, array $data): ClassSession
    {
        return DB::transaction(function () use ($sessionId, $data) {
            $session = $this->guardSessionEditable($sessionId);

            $updated = $this->sessions->cancel($sessionId, $data);

            $this->recordChange($session, 'cancel', $session->status, ClassSession::STATUS_CANCELLED, $data['reason']);

            return $updated;
        });
    }

    /**
     * @throws \RuntimeException when the session has no timetable or is already completed (BR-04)
     */
    private function guardSessionEditable(int $sessionId): ClassSession
    {
        $session = ClassSession::findOrFail($sessionId);

        if (! $session->timetable_id) {
            throw new \RuntimeException('Buổi học này không thuộc thời khóa biểu nào.');
        }

        if ($session->status === ClassSession::STATUS_COMPLETED) {
            throw new \RuntimeException('Buổi học đã hoàn thành, không thể sửa.'); // BR-04
        }

        return $session;
    }

    private function recordChange(ClassSession $session, string $type, ?string $old, ?string $new, ?string $reason): void
    {
        TimetableChange::create([
            'timetable_id' => $session->timetable_id,
            'session_id' => $session->id,
            'change_type' => $type,
            'old_value' => $old,
            'new_value' => $new,
            'reason' => $reason,
            'changed_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }

    private function teacherLabel(?int $teacherId): ?string
    {
        if (! $teacherId) {
            return null;
        }

        return Teacher::where('id', $teacherId)->value('full_name') ?? "#{$teacherId}";
    }

    private function roomLabel(?int $roomId): ?string
    {
        if (! $roomId) {
            return null;
        }

        return Room::where('id', $roomId)->value('room_name') ?? "#{$roomId}";
    }

    /**
     * Sessions within a date range, optionally scoped to a class / teacher / room.
     */
    public function calendar(array $params = [])
    {
        $query = $this->sessionQuery($params);

        return $query->orderBy('session_date')->orderBy('start_time')->get();
    }

    public function teacherSchedule($teacherId, array $params = [])
    {
        return $this->sessionQuery($params)->where('teacher_id', $teacherId)
            ->orderBy('session_date')->orderBy('start_time')->get();
    }

    public function roomSchedule($roomId, array $params = [])
    {
        return $this->sessionQuery($params)->where('room_id', $roomId)
            ->orderBy('session_date')->orderBy('start_time')->get();
    }

    public function studentSchedule($studentId, array $params = [])
    {
        $classIds = ClassStudent::where('student_id', $studentId)->pluck('class_id');

        return $this->sessionQuery($params)->whereIn('class_id', $classIds)
            ->orderBy('session_date')->orderBy('start_time')->get();
    }

    private function sessionQuery(array $params): Builder
    {
        $query = ClassSession::query()->with(['classRoom', 'teacher', 'room', 'timetable']);

        foreach (['class_id', 'teacher_id', 'room_id', 'timetable_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['status'])) {
            $statuses = is_array($params['status'])
                ? $params['status']
                : explode(',', $params['status']);
            $query->whereIn('status', $statuses);
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('session_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('session_date', '<=', $params['date_to']);
        }

        return $query;
    }

    /**
     * Expand the schedule config into concrete sessions: { date, start, end }.
     *
     * @return array<int, array{date: string, start: string, end: string}>
     */
    private function planSessions(array $data): array
    {
        $plan = [];

        if (($data['schedule_pattern'] ?? 'fixed_weekly') === 'specific_dates') {
            foreach ($data['dates'] ?? [] as $d) {
                $plan[] = ['date' => Carbon::parse($d['date'])->toDateString(), 'start' => $this->time($d['start_time']), 'end' => $this->time($d['end_time'])];
            }

            return $plan;
        }

        $rules = $data['rules'] ?? [];
        $date = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        while ($date->lte($end)) {
            foreach ($rules as $rule) {
                if ($date->dayOfWeekIso === (int) $rule['day_of_week']) {
                    $plan[] = ['date' => $date->toDateString(), 'start' => $this->time($rule['start_time']), 'end' => $this->time($rule['end_time'])];
                }
            }
            $date->addDay();
        }

        return $plan;
    }

    /**
     * @param  array<int, array{date: string, start: string, end: string}>  $plan
     */
    private function generateSessions(Timetable $timetable, array $plan, array $data): void
    {
        $no = 0;
        foreach ($plan as $s) {
            $no++;
            // No Lesson is paired here — the teacher picks which of the class's
            // (possibly several) lesson plans this session follows when they
            // start it (see ClassSessionService::start()), and a session may
            // legitimately have none at all (e.g. an exam day).
            ClassSession::create([
                'class_id' => $data['class_room_id'],
                'timetable_id' => $timetable->id,
                'session_no' => $no,
                'name' => 'Buổi '.$no,
                'session_date' => $s['date'],
                'start_time' => $s['start'],
                'end_time' => $s['end'],
                'teacher_id' => $data['teacher_id'] ?? null,
                'room_id' => $data['room_id'] ?? null,
                'status' => ClassSession::STATUS_UPCOMING,
            ]);
        }
    }

    /**
     * @param  array<int, array{date: string, start: string, end: string}>  $plan
     *
     * @throws \RuntimeException
     */
    private function assertNoConflicts(array $plan, array $data): void
    {
        foreach ($plan as $s) {
            if (! empty($data['teacher_id']) && $this->hasOverlap('teacher_id', $data['teacher_id'], $s)) {
                throw new \RuntimeException("Giáo viên đã có lịch trùng vào {$s['date']} {$s['start']}."); // BR-02
            }
            if (! empty($data['room_id']) && $this->hasOverlap('room_id', $data['room_id'], $s)) {
                throw new \RuntimeException("Phòng học đã có lịch trùng vào {$s['date']} {$s['start']}."); // BR-01
            }
        }
    }

    /**
     * @param  array{date: string, start: string, end: string}  $s
     */
    private function hasOverlap(string $column, $value, array $s): bool
    {
        return ClassSession::where($column, $value)
            ->whereDate('session_date', $s['date'])
            ->where('status', '!=', ClassSession::STATUS_CANCELLED)
            ->where('start_time', '<', $s['end'])
            ->where('end_time', '>', $s['start'])
            ->exists();
    }

    /**
     * @throws \RuntimeException
     */
    private function assertCapacity(array $data): void
    {
        if (empty($data['room_id'])) {
            return;
        }

        $capacity = (int) Room::where('id', $data['room_id'])->value('capacity');
        $students = ClassStudent::where('class_id', $data['class_room_id'])
            ->where('status', 'active')
            ->count();

        if ($capacity > 0 && $students > $capacity) {
            throw new \RuntimeException('Số học viên vượt quá sức chứa phòng học.'); // BR-03
        }
    }

    private function time(string $value): string
    {
        return Carbon::parse($value)->format('H:i:s');
    }

    private function generateCode(): string
    {
        $next = (int) (Timetable::withTrashed()->max('id') ?? 0) + 1;

        return 'TKB'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
