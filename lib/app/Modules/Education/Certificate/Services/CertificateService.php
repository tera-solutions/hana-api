<?php

namespace App\Modules\Education\Certificate\Services;

use App\Modules\Education\Attendance\Models\Attendance;
use App\Modules\Education\Certificate\Models\Certificate;
use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Grade\Models\Grade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * EDU-18 Certificate. Eligibility is informational (final score, attendance
 * rate, outstanding debt) — the teacher makes the final call on issuing, per
 * BRD v2 §4 ("Teacher tự phát hành, không có bước trình duyệt").
 */
class CertificateService
{
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
                    'student_code' => $enrollment->student->student_code,
                    'full_name' => $enrollment->student->full_name,
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
            'student_name' => $certificate->student->full_name ?? null,
            'class_name' => $certificate->classRoom->name ?? null,
        ];
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
