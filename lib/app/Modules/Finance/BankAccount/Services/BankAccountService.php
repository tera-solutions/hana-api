<?php

namespace App\Modules\Finance\BankAccount\Services;

use App\Modules\Finance\BankAccount\Models\BankAccount;
use App\Modules\HR\Teacher\Models\Teacher;

/**
 * Self-service access to the acting teacher's own HR profile bank account
 * (`fin_bank_accounts`, one per teacher) — the same record an admin can set
 * via `Teacher` create/update, exposed here so a teacher can manage it directly
 * without `teacher.update` (withheld from TEACHER_ROLE, see teacher.md).
 */
class BankAccountService
{
    public function mine(int $businessId, int $userId): ?BankAccount
    {
        return $this->teacherFor($businessId, $userId)->bankAccount;
    }

    /**
     * @throws \RuntimeException
     */
    public function updateMine(int $businessId, int $userId, array $data): BankAccount
    {
        $teacher = $this->teacherFor($businessId, $userId);

        $teacher->bankAccount()->updateOrCreate([], [
            'bank_name' => $data['bank_name'],
            'bank_account_number' => $data['bank_account_number'],
            'bank_account_holder' => $data['bank_account_holder'],
            'bank_branch' => $data['bank_branch'] ?? null,
        ]);

        return $teacher->bankAccount()->first();
    }

    /**
     * @throws \RuntimeException
     */
    private function teacherFor(int $businessId, int $userId): Teacher
    {
        $teacher = Teacher::where('business_id', $businessId)
            ->where('user_id', $userId)
            ->first();

        if (! $teacher) {
            throw new \RuntimeException('Không tìm thấy hồ sơ giáo viên.');
        }

        return $teacher;
    }
}
