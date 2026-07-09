<?php

namespace App\Modules\Education\Exam\Services;

use App\Modules\Education\ClassRoom\Models\ClassStudent;
use App\Modules\Education\Exam\Models\ExamRegistration;
use App\Modules\Education\Exam\Models\ExamSession;
use App\Modules\Education\Student\Models\Student;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class ExamSessionService
{
    use HandlesEntityQueries;

    /**
     * Paginated, filterable list of exam sittings (exam.md §XV).
     */
    public function paginate(array $params = [])
    {
        $query = ExamSession::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->whereHas('exam', fn ($q) => $q->where('exam_name', 'like', "%{$search}%"));
        }

        foreach (['exam_id', 'class_room_id', 'room_id', 'teacher_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['exam_date'])) {
            $query->whereDate('exam_date', $params['exam_date']);
        }

        if ($scope = TeacherScope::current()) {
            $scope->constrainExamSessions($query);
        }

        $this->applySort($query, $params, ['exam_date', 'start_time', 'status', 'created_at']);

        return $query->with(['exam', 'classRoom', 'room', 'teacher'])
            ->withCount('registrations')
            ->paginate($this->resolvePerPage($params));
    }

    /**
     * Detail with registration roster and result roster (exam.md §XIV).
     */
    public function detail($id): ExamSession
    {
        $query = ExamSession::query();

        if ($scope = TeacherScope::current()) {
            $scope->constrainExamSessions($query);
        }

        return $query->with([
            'exam', 'classRoom', 'room', 'teacher',
            'registrations.student', 'results.student',
        ])->findOrFail($id);
    }

    /**
     * Create a sitting, asserting no room/invigilator clash (BR001, BR002).
     *
     * @throws \RuntimeException
     */
    public function create(array $data): ExamSession
    {
        return DB::transaction(function () use ($data) {
            $this->guardScheduleConflicts($data);

            $session = ExamSession::create([
                ...$data,
                'status' => ExamSession::STATUS_SCHEDULED,
            ]);

            return $this->detail($session->id);
        });
    }

    /**
     * Update a sitting, re-checking schedule conflicts against other sessions (BR001, BR002).
     *
     * @throws \RuntimeException
     */
    public function update($id, array $data): ExamSession
    {
        return DB::transaction(function () use ($id, $data) {
            $session = $this->scopedSession($id);

            unset($data['id'], $data['exam_id']);

            $this->guardScheduleConflicts([...$session->only(['room_id', 'teacher_id', 'exam_date', 'start_time', 'end_time']), ...$data], $session->id);

            $session->update($data);

            return $this->detail($session->id);
        });
    }

    public function delete($id): void
    {
        $this->scopedSession($id)->delete();
    }

    // ── Registration (exam.md §IX) ───────────────────────────────────────────────

    /**
     * Auto-register every active student of a class (exam.md §IX "Theo lớp").
     *
     * @return array{registered: int}
     *
     * @throws \RuntimeException
     */
    public function registerByClass($sessionId, int $classRoomId): array
    {
        $studentIds = ClassStudent::where('class_id', $classRoomId)
            ->where('status', 'active')
            ->pluck('student_id');

        return $this->seedRegistrations($sessionId, $studentIds);
    }

    /**
     * Manually register an explicit list of students (exam.md §IX "Theo học viên").
     *
     * @param  array<int>  $studentIds
     * @return array{registered: int}
     *
     * @throws \RuntimeException
     */
    public function registerByStudent($sessionId, array $studentIds): array
    {
        return $this->seedRegistrations($sessionId, collect($studentIds));
    }

    /**
     * Seed a REGISTERED row per student (BR004 idempotent, BR005 session must be open).
     *
     * @return array{registered: int}
     *
     * @throws \RuntimeException
     */
    private function seedRegistrations($sessionId, Collection $studentIds): array
    {
        return DB::transaction(function () use ($sessionId, $studentIds) {
            $session = $this->scopedSession($sessionId);

            // BR005: no registration once the sitting is closed.
            if ($session->status === ExamSession::STATUS_CLOSED) {
                throw new \RuntimeException('Kỳ thi đã đóng, không thể đăng ký.');
            }

            $studentIds = $studentIds->map(fn ($v) => (int) $v)->unique()->values();

            if ($studentIds->isEmpty()) {
                throw new \RuntimeException('Không có học viên nào để đăng ký dự thi.');
            }

            // BR004: a student is registered iff a row already exists; seed only the gaps.
            $existing = ExamRegistration::where('exam_session_id', $session->id)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id');

            $newStudentIds = $studentIds->diff($existing)->values();

            if ($newStudentIds->isEmpty()) {
                return ['registered' => 0];
            }

            $now = now();
            $actorId = Auth::guard('api')->id() ?? Auth::id();

            ExamRegistration::insertOrIgnore($newStudentIds->map(fn (int $studentId) => [
                'exam_session_id' => $session->id,
                'student_id' => $studentId,
                'status' => ExamRegistration::STATUS_REGISTERED,
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            return ['registered' => $newStudentIds->count()];
        });
    }

    /**
     * Resolve a sitting within the caller's teacher scope, 404-ing when the
     * sitting is out of scope (a teacher may only manage their own sittings).
     */
    private function scopedSession($id): ExamSession
    {
        $query = ExamSession::query();

        if ($scope = TeacherScope::current()) {
            $scope->constrainExamSessions($query);
        }

        return $query->findOrFail($id);
    }

    /**
     * BR001/BR002: reject overlapping bookings of the same room or invigilator on the
     * same date. Two sittings overlap when start < other end AND end > other start.
     *
     * @throws \RuntimeException
     */
    private function guardScheduleConflicts(array $data, ?int $ignoreId = null): void
    {
        $date = $data['exam_date'] ?? null;
        $start = $data['start_time'] ?? null;
        $end = $data['end_time'] ?? null;

        if (! $date || ! $start || ! $end) {
            return;
        }

        $overlaps = function ($column, $value) use ($date, $start, $end, $ignoreId) {
            return ExamSession::where($column, $value)
                ->whereDate('exam_date', $date)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->exists();
        };

        if (! empty($data['room_id']) && $overlaps('room_id', $data['room_id'])) {
            throw new \RuntimeException('Phòng thi đã được sử dụng trong khung giờ này.'); // BR001
        }

        if (! empty($data['teacher_id']) && $overlaps('teacher_id', $data['teacher_id'])) {
            throw new \RuntimeException('Giám thị đã có lịch coi thi trong khung giờ này.'); // BR002
        }
    }
}
