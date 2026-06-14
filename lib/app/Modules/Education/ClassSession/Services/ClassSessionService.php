<?php

namespace App\Modules\Education\ClassSession\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSchedule\Models\ClassSchedule;
use App\Modules\Education\ClassSession\Models\ClassSession;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class ClassSessionService
{
    use HandlesEntityQueries;

    private const WITH = ['classRoom', 'teacher', 'substituteTeacher', 'tags'];

    /**
     * Paginated, searchable, filterable list (spec §3–§4).
     */
    public function paginate(array $params = []): LengthAwarePaginator
    {
        $query = ClassSession::query();

        if (! empty($params['class_id'])) {
            $query->where('class_id', $params['class_id']);
        }

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['teacher_id'])) {
            $query->where('teacher_id', $params['teacher_id']);
        }

        if (! empty($params['room_id'])) {
            $query->where('room_id', $params['room_id']);
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('session_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $query->whereDate('session_date', '<=', $params['date_to']);
        }

        if (! empty($params['tag_ids'])) {
            $tagIds = (array) $params['tag_ids'];
            $query->whereHas('tags', fn ($q) => $q->whereIn('crm_tags.id', $tagIds));
        }

        $this->applySort($query, $params, ['session_no', 'name', 'session_date', 'start_time', 'status', 'created_at'], 'session_date');

        return $query->with(self::WITH)->paginate($this->resolvePerPage($params));
    }

    public function find($id): ClassSession
    {
        return ClassSession::with(self::WITH)->findOrFail($id);
    }

    /**
     * Create a single session (spec §5).
     *
     * @throws \RuntimeException on schedule conflict
     */
    public function create(array $data): ClassSession
    {
        return DB::transaction(function () use ($data) {
            $classId = $data['class_id'];
            ClassRoom::findOrFail($classId);

            $this->assertNoConflict($classId, $data['session_date'], $data['start_time'], $data['end_time'], $data);

            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids']);

            $data['session_no'] = $this->nextSessionNo($classId);
            $data['code'] = $this->makeCode($classId, $data['session_no']);
            $data['status'] = ClassSession::STATUS_UPCOMING;

            $session = ClassSession::create($data);

            if ($tagIds !== null) {
                $session->tags()->sync($tagIds);
            }

            return $this->find($session->id);
        });
    }

    /**
     * Bulk-generate sessions from the class schedules over a date range (spec §6).
     *
     * @return array{created: int, skipped: int}
     */
    public function generate($classId, array $data): array
    {
        return DB::transaction(function () use ($classId, $data) {
            ClassRoom::findOrFail($classId);

            $from = $data['from_date'];
            $to = $data['to_date'];
            $override = ! empty($data['override']);

            $schedules = ClassSchedule::where('class_id', $classId)->get();

            if ($override) {
                ClassSession::where('class_id', $classId)
                    ->whereDate('session_date', '>=', $from)
                    ->whereDate('session_date', '<=', $to)
                    ->where('attendance_locked', false)
                    ->delete();
            }

            $no = $this->nextSessionNo($classId);
            $created = 0;
            $skipped = 0;

            foreach (CarbonPeriod::create($from, $to) as $date) {
                $weekday = $date->dayOfWeekIso; // 1 (Mon) … 7 (Sun)
                $dateStr = $date->toDateString();

                foreach ($schedules->where('weekday', $weekday) as $schedule) {
                    $exists = ClassSession::where('class_id', $classId)
                        ->whereDate('session_date', $dateStr)
                        ->where('start_time', $schedule->start_time)
                        ->exists();

                    if ($exists) {
                        $skipped++;

                        continue;
                    }

                    ClassSession::create([
                        'class_id' => $classId,
                        'schedule_id' => $schedule->id,
                        'session_no' => $no,
                        'code' => $this->makeCode($classId, $no),
                        'name' => 'Buổi '.$no,
                        'session_date' => $dateStr,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'status' => ClassSession::STATUS_UPCOMING,
                    ]);

                    $no++;
                    $created++;
                }
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    /**
     * Update a session (spec §7). Blocked once attendance is locked.
     *
     * @throws \RuntimeException
     */
    public function update($id, array $data): ClassSession
    {
        return DB::transaction(function () use ($id, $data) {
            $session = ClassSession::findOrFail($id);

            if ($session->attendance_locked) {
                throw new \RuntimeException('Buổi học đã chốt điểm danh, không thể cập nhật.');
            }

            $tagIds = $data['tag_ids'] ?? null;
            unset($data['tag_ids'], $data['id'], $data['class_id'], $data['code'], $data['session_no'], $data['status']);

            $date = $data['session_date'] ?? $session->session_date->toDateString();
            $start = $data['start_time'] ?? $session->start_time;
            $end = $data['end_time'] ?? $session->end_time;

            $this->assertNoConflict($session->class_id, $date, $start, $end, $data, $session->id);

            $session->fill($data)->save();

            if ($tagIds !== null) {
                $session->tags()->sync($tagIds);
            }

            return $this->find($session->id);
        });
    }

    /**
     * Cancel a session (spec §11): no revenue, no attendance recorded.
     *
     * @throws \RuntimeException
     */
    public function cancel($id, array $data): ClassSession
    {
        $session = ClassSession::findOrFail($id);

        if ($session->status === ClassSession::STATUS_CANCELLED) {
            throw new \RuntimeException('Buổi học đã được hủy.');
        }

        if ($session->status === ClassSession::STATUS_COMPLETED) {
            throw new \RuntimeException('Buổi học đã kết thúc, không thể hủy.');
        }

        $session->update([
            'status' => ClassSession::STATUS_CANCELLED,
            'revenue_amount' => 0,
            'note' => $data['reason'],
        ]);

        return $this->find($id);
    }

    public function delete($id): void
    {
        ClassSession::findOrFail($id)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Reject overlapping sessions sharing the class, teacher or room at the same
     * time (spec §5 "Trùng lịch"). Cancelled sessions are ignored.
     *
     * @throws \RuntimeException
     */
    private function assertNoConflict(int $classId, string $date, string $start, string $end, array $data, ?int $excludeId = null): void
    {
        $teacherId = $data['teacher_id'] ?? null;
        $roomId = $data['room_id'] ?? null;

        $query = ClassSession::whereDate('session_date', $date)
            ->where('status', '!=', ClassSession::STATUS_CANCELLED)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->where(function ($q) use ($classId, $teacherId, $roomId) {
                $q->where('class_id', $classId);

                if ($teacherId !== null) {
                    $q->orWhere('teacher_id', $teacherId)
                        ->orWhere('substitute_teacher_id', $teacherId);
                }

                if ($roomId !== null) {
                    $q->orWhere('room_id', $roomId);
                }
            });

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \RuntimeException('Trùng lịch: phòng học, giáo viên hoặc lớp học đã có buổi trong khung giờ này.');
        }
    }

    private function nextSessionNo(int $classId): int
    {
        return (int) ClassSession::where('class_id', $classId)->max('session_no') + 1;
    }

    private function makeCode(int $classId, int $sessionNo): string
    {
        $classCode = ClassRoom::whereKey($classId)->value('code');

        return $classCode
            ? sprintf('%s-B%02d', $classCode, $sessionNo)
            : sprintf('B%02d', $sessionNo);
    }
}
