<?php

namespace App\Modules\Education\Attendance\Services;

use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Support\TeacherScope;
use Package\Database\Concerns\HandlesEntityQueries;

class AttendanceService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['session', 'student'];

    /**
     * Paginated, filterable attendance list ("Danh sách chuyên cần").
     */
    public function paginate(array $params = [])
    {
        $query = Attendance::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        foreach (['session_id', 'student_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['class_id'])) {
            $query->whereHas('session', fn ($q) => $q->where('class_id', $params['class_id']));
        }

        if (! empty($params['date'])) {
            $query->whereHas('session', fn ($q) => $q->whereDate('session_date', $params['date']));
        }
        if (! empty($params['date_from'])) {
            $query->whereHas('session', fn ($q) => $q->whereDate('session_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('session', fn ($q) => $q->whereDate('session_date', '<=', $params['date_to']));
        }

        if ($scope = TeacherScope::current()) {
            $query->whereHas('session', fn ($q) => $scope->constrainSessions($q));
        }

        $this->applySort($query, $params, ['status', 'checkin_time', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    /**
     * One row per (session, student) — `updateOrCreate` so re-marking an
     * already-recorded student (e.g. a retried save) never hits the unique
     * constraint instead of just updating the existing row.
     */
    public function create(array $data): Attendance
    {
        $sessionId = (int) ($data['session_id'] ?? 0);
        $studentId = (int) ($data['student_id'] ?? 0);

        $session = ClassSession::findOrFail($sessionId);

        if ($scope = TeacherScope::current()) {
            $scope->authorizeSession($session);
        }

        $this->guardWritable($session);

        $attendance = Attendance::updateOrCreate(
            ['session_id' => $sessionId, 'student_id' => $studentId],
            $this->fillableAttributes($data),
        );

        return $attendance->fresh(self::RELATIONS);
    }

    public function update($id, array $data): Attendance
    {
        $attendance = Attendance::with('session')->findOrFail($id);

        if ($scope = TeacherScope::current()) {
            $scope->authorizeSession($attendance->session);
        }

        $this->guardWritable($attendance->session);

        $attendance->update($this->fillableAttributes($data));

        return $attendance->fresh(self::RELATIONS);
    }

    /**
     * Spec §7/§11: a cancelled session never records attendance, and once
     * attendance is locked (attendance_locked = true) it can no longer be
     * changed.
     */
    private function guardWritable(ClassSession $session): void
    {
        if ($session->status === ClassSession::STATUS_CANCELLED) {
            throw new \RuntimeException('Buổi học đã bị hủy, không thể điểm danh.');
        }

        if ($session->attendance_locked) {
            throw new \RuntimeException('Buổi học đã chốt điểm danh, không thể thay đổi.');
        }
    }

    private function fillableAttributes(array $data): array
    {
        $attrs = ['status' => $data['status']];

        foreach (['note', 'checkin_time', 'checkout_time'] as $field) {
            if (array_key_exists($field, $data)) {
                $attrs[$field] = $data[$field];
            }
        }

        return $attrs;
    }
}
