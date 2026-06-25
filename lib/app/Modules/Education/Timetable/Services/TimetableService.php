<?php

namespace App\Modules\Education\Timetable\Services;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Timetable\Models\Timetable;
use App\Modules\Education\Timetable\Models\TimetableRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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
        return Timetable::with(['course', 'classRoom', 'rules', 'sessions' => fn ($q) => $q->orderBy('session_date')->orderBy('start_time')])
            ->findOrFail($id);
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

            return $this->find($timetable->id);
        });
    }

    public function update($id, array $data): Timetable
    {
        return DB::transaction(function () use ($id, $data) {
            $timetable = Timetable::findOrFail($id);
            $timetable->update($data);

            return $this->find($id);
        });
    }

    public function delete($id): void
    {
        Timetable::findOrFail($id)->delete();
    }

    /**
     * Sessions within a date range, optionally scoped to a class / teacher / room.
     */
    public function calendar(array $params = [])
    {
        return $this->sessionQuery($params)->orderBy('session_date')->orderBy('start_time')->get();
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
        $classIds = DB::table('edu_class_students')->where('student_id', $studentId)->pluck('class_id');

        return $this->sessionQuery($params)->whereIn('class_id', $classIds)
            ->orderBy('session_date')->orderBy('start_time')->get();
    }

    private function sessionQuery(array $params): Builder
    {
        $query = ClassSession::query();

        foreach (['class_id', 'teacher_id', 'room_id', 'timetable_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
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

        $capacity = (int) DB::table('edu_rooms')->where('id', $data['room_id'])->value('capacity');
        $students = DB::table('edu_class_students')
            ->where('class_id', $data['class_room_id'])
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
