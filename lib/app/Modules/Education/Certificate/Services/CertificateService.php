<?php

namespace App\Modules\Education\Certificate\Services;

use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\Certificate\Models\Certificate;
use App\Modules\Education\CertificateTemplate\Models\CertificateTemplate;
use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Grade\Models\Grade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * EDU-18 Certificate. Eligibility is informational (final score, attendance
 * rate, outstanding debt) — the teacher makes the final call on issuing, per
 * BRD v2 §4 ("Teacher tự phát hành, không có bước trình duyệt").
 */
class CertificateService
{
    use HandlesEntityQueries;

    /**
     * Roster of a class with everything needed to decide who to issue to.
     */
    public function eligibility($classId): array
    {
        $roster = Enrollment::where('class_id', $classId)
            ->whereIn('status', Enrollment::ACTIVE_STATUSES)
            ->with('student')
            ->get();

        $finalScores = Grade::where('class_id', $classId)
            ->where('type', Grade::TYPE_FINAL)
            ->get()
            ->keyBy('student_id');

        $certificates = Certificate::where('class_id', $classId)
            ->orderByDesc('issued_at')
            ->get()
            ->groupBy('student_id');

        return $roster->map(function (Enrollment $enrollment) use ($finalScores, $classId) {
            $studentId = $enrollment->student_id;
            $latestCertificate = ($certificates ?? collect())->get($studentId, collect())->first();

            return [
                'student_id' => $studentId,
                'student' => $enrollment->student ? [
                    'id' => $enrollment->student->id,
                    'student_code' => $enrollment->student->code,
                    'full_name' => $enrollment->student->name,
                ] : null,
                'final_score' => $finalScores->get($studentId)?->score,
                'attendance_rate' => $this->attendanceRate($classId, $studentId),
                'debt_amount' => (float) ($enrollment->debt_amount ?? 0),
                'certificate' => $latestCertificate,
            ];
        })->values()->all();
    }

    /**
     * @throws \RuntimeException
     */
    public function issue($classId, $studentId): Certificate
    {
        $finalGrade = Grade::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->where('type', Grade::TYPE_FINAL)
            ->first();

        if (! $finalGrade) {
            throw new \RuntimeException('Học viên chưa có điểm tổng kết — hãy chốt điểm lớp trước.');
        }

        $alreadyIssued = Certificate::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->where('status', Certificate::STATUS_ISSUED)
            ->exists();

        if ($alreadyIssued) {
            throw new \RuntimeException('Học viên đã có chứng chỉ đang hiệu lực cho lớp này.');
        }

        return DB::transaction(function () use ($classId, $studentId, $finalGrade) {
            $seq = Certificate::lockForUpdate()->count() + 1;

            return Certificate::create([
                'student_id' => $studentId,
                'class_id' => $classId,
                'certificate_no' => 'CC'.now()->format('y').str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                'verify_token' => (string) Str::uuid(),
                'status' => Certificate::STATUS_ISSUED,
                'final_score' => $finalGrade->score,
                'issued_at' => now(),
            ]);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function revoke($id, ?string $reason): Certificate
    {
        $certificate = Certificate::findOrFail($id);

        if ($certificate->status === Certificate::STATUS_REVOKED) {
            throw new \RuntimeException('Chứng chỉ đã bị thu hồi trước đó.');
        }

        $certificate->update([
            'status' => Certificate::STATUS_REVOKED,
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);

        return $certificate;
    }

    public function listByClass($classId)
    {
        return Certificate::where('class_id', $classId)->with('student')->orderByDesc('issued_at')->get();
    }

    public function listByStudent($studentId)
    {
        return Certificate::where('student_id', $studentId)->with('classRoom')->orderByDesc('issued_at')->get();
    }

    /**
     * Summary counters + paginated, filterable list for the "Chứng nhận học
     * viên" screen (teacher-app-076).
     */
    public function summary(array $params = []): array
    {
        $query = Certificate::query();

        foreach (['template_id', 'course_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->whereHas('student', fn ($q) => $q->where('full_name', 'like', "%{$search}%"));
        }

        $this->applySort($query, $params, ['issued_at', 'status', 'created_at'], 'issued_at');

        return [
            'summary' => [
                'issued' => Certificate::where('status', Certificate::STATUS_ISSUED)->count(),
                'templates' => CertificateTemplate::where('status', CertificateTemplate::STATUS_ACTIVE)->count(),
            ],
            'paginator' => $query->with(['student', 'course', 'classRoom', 'template'])->paginate($this->resolvePerPage($params)),
        ];
    }

    /**
     * Students of a course (across all its classes) whose completion rate
     * meets the threshold — the "Bước 1" roster for bulk issuance.
     *
     * @return array<int, array{id: int, name: ?string, completion_rate: ?float}>
     */
    public function eligibleStudentsByCourse(int $courseId, float $threshold = 100.0): array
    {
        $classIds = ClassRoom::where('course_id', $courseId)->pluck('id');

        return Enrollment::whereIn('class_id', $classIds)
            ->whereIn('status', Enrollment::ACTIVE_STATUSES)
            ->with('student')
            ->get()
            ->unique('student_id')
            ->map(function (Enrollment $enrollment) use ($classIds) {
                return [
                    'id' => $enrollment->student_id,
                    'name' => $enrollment->student?->name,
                    'completion_rate' => $this->courseCompletionRate($classIds, $enrollment->student_id),
                ];
            })
            ->filter(fn ($row) => $row['completion_rate'] !== null && $row['completion_rate'] >= $threshold)
            ->values()
            ->all();
    }

    /**
     * Issue one certificate per student for a course, skipping students who
     * already hold a currently-issued certificate for it. No approval gate —
     * same "teacher issues directly" decision as the single-class flow.
     *
     * @param  int[]  $studentIds
     * @return array{issued_count: int, certificate_ids: int[]}
     */
    public function bulkIssue(int $courseId, array $studentIds, ?int $templateId): array
    {
        return DB::transaction(function () use ($courseId, $studentIds, $templateId) {
            $alreadyIssued = Certificate::where('course_id', $courseId)
                ->where('status', Certificate::STATUS_ISSUED)
                ->whereIn('student_id', $studentIds)
                ->pluck('student_id')
                ->all();

            $certificateIds = [];
            $seq = Certificate::lockForUpdate()->count();

            foreach (array_diff($studentIds, $alreadyIssued) as $studentId) {
                $seq++;

                $certificate = Certificate::create([
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'template_id' => $templateId,
                    'certificate_no' => 'CC'.now()->format('y').str_pad((string) $seq, 5, '0', STR_PAD_LEFT),
                    'verify_token' => (string) Str::uuid(),
                    'status' => Certificate::STATUS_ISSUED,
                    'issued_at' => now(),
                ]);

                $certificateIds[] = $certificate->id;
            }

            return ['issued_count' => count($certificateIds), 'certificate_ids' => $certificateIds];
        });
    }

    /**
     * Render a certificate as a downloadable PDF.
     */
    public function downloadPdf($id): \Barryvdh\DomPDF\PDF
    {
        $certificate = Certificate::with(['student', 'course', 'classRoom'])->findOrFail($id);

        return Pdf::loadView('certificates.pdf', ['certificate' => $certificate])->setPaper('a4', 'landscape');
    }

    /**
     * Public lookup by QR token — deliberately unscoped (no auth, no tenant
     * context) so a parent/employer scanning the QR code doesn't need an
     * account. Returns null if the token doesn't exist.
     */
    public function verify(string $token): ?array
    {
        $certificate = Certificate::with(['student', 'classRoom'])->where('verify_token', $token)->first();

        if (! $certificate) {
            return null;
        }

        return [
            'certificate_no' => $certificate->certificate_no,
            'status' => $certificate->status,
            'issued_at' => $certificate->issued_at,
            'revoked_at' => $certificate->revoked_at,
            'final_score' => $certificate->final_score,
            'student_name' => $certificate->student->name ?? null,
            'class_name' => $certificate->classRoom->name ?? null,
        ];
    }

    /**
     * A student's completion rate for a course = the average of their
     * attendance rate across every class of the course they attended.
     */
    private function courseCompletionRate($classIds, $studentId): ?float
    {
        $rates = [];

        foreach ($classIds as $classId) {
            $rate = $this->attendanceRate($classId, $studentId);
            if ($rate !== null) {
                $rates[] = $rate;
            }
        }

        return $rates === [] ? null : round(array_sum($rates) / count($rates), 1);
    }

    private function attendanceRate($classId, $studentId): ?float
    {
        $sessionIds = ClassSession::where('class_id', $classId)->pluck('id');
        if ($sessionIds->isEmpty()) {
            return null;
        }

        $total = Attendance::where('student_id', $studentId)->whereIn('session_id', $sessionIds)->count();
        if ($total === 0) {
            return null;
        }

        $attended = Attendance::where('student_id', $studentId)
            ->whereIn('session_id', $sessionIds)
            ->whereIn('status', [Attendance::STATUS_PRESENT, Attendance::STATUS_LATE])
            ->count();

        return round($attended / $total * 100, 1);
    }
}
