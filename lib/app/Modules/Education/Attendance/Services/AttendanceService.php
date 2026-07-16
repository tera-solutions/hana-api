<?php

namespace App\Modules\Education\Attendance\Services;

use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\ClassSession\Models\ClassSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
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
        $query = $this->filteredQuery($params);

        $this->applySort($query, $params, ['status', 'checkin_time', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    /**
     * The filtered, teacher-scoped base query shared by paginate() and
     * export() — no sort or eager-loads applied.
     */
    private function filteredQuery(array $params): Builder
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

        return $query;
    }

    /**
     * Exports the filtered attendance list as a CSV, stored under the public
     * disk and returned as a downloadable link (spec: "Xuất báo cáo").
     */
    public function export(array $params = []): array
    {
        $rows = $this->filteredQuery($params)
            ->with(self::RELATIONS)
            ->orderBy('created_at')
            ->get();

        $now = now();
        $fileName = "export_attendance_{$now->getTimestamp()}.csv";
        $relativePath = "assets/export/attendance/{$fileName}";

        $handle = fopen('php://temp', 'w+');
        // UTF-8 BOM so Excel opens Vietnamese diacritics correctly.
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Mã HV', 'Học viên', 'Buổi học', 'Ngày', 'Trạng thái', 'Ghi chú']);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->student?->code,
                $row->student?->name,
                $row->session?->name,
                optional($row->session?->session_date)->format('Y-m-d') ?? $row->session?->session_date,
                $row->status,
                $row->note,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($relativePath, $csv);

        return [
            'file_name' => $fileName,
            'created_at' => $now,
            'link' => Storage::disk('public')->url($relativePath),
        ];
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
