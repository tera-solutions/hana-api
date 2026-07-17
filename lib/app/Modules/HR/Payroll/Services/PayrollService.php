<?php

namespace App\Modules\HR\Payroll\Services;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\HR\Teacher\Models\Payroll;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\HR\Timesheet\Services\TimesheetService;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * Lương giáo viên = giờ dạy thực tế (nguồn `TimesheetService`, buổi đã điểm
 * danh) × đơn giá/giờ (`hr_teachers.hourly_rate`) + thưởng − phạt (project
 * decision 2026-07-17). Không có cổng thanh toán: `generate()` chỉ TÍNH và ghi
 * lại một dòng `hr_payrolls`, việc chi trả nằm ngoài hệ thống — admin-only,
 * cùng nguyên tắc với Wallet Request (không để giáo viên tự đặt thưởng/phạt
 * cho chính mình).
 */
class PayrollService
{
    use HandlesEntityQueries;

    public function __construct(private readonly TimesheetService $timesheet) {}

    public function paginate(int $teacherId, array $params = [])
    {
        $query = Payroll::query()->where('teacher_id', $teacherId);

        $this->applySort($query, $params, ['year', 'month', 'total_salary']);
        if (empty($params['sort_by'])) {
            $query->orderByDesc('year')->orderByDesc('month');
        }

        return $query->paginate($this->resolvePerPage($params));
    }

    public function find(int $id): Payroll
    {
        return Payroll::findOrFail($id);
    }

    /**
     * A payroll period's detail plus a live "class income" breakdown — the
     * per-class hours × rate that make up `base_salary`. Not stored as line
     * items; recomputed from `TimesheetService` each time, same source `generate()` used.
     */
    public function detail(int $id): array
    {
        $payroll = $this->find($id);
        $teacher = Teacher::findOrFail($payroll->teacher_id);

        $monthParam = sprintf('%04d-%02d', $payroll->year, $payroll->month);
        $sessions = $this->timesheet->sessions($payroll->teacher_id, ['month' => $monthParam, 'per_page' => 500]);

        $byClass = [];
        foreach ($sessions->items() as $session) {
            /** @var ClassSession $session */
            $classId = $session->class_id;
            $byClass[$classId] ??= [
                'class_id' => $classId,
                'class_name' => $session->classRoom?->name,
                'session_count' => 0,
                'hours' => 0.0,
            ];
            $byClass[$classId]['session_count']++;
            $byClass[$classId]['hours'] += $this->timesheet->sessionHours($session);
        }

        $rate = (float) ($teacher->hourly_rate ?? 0);
        $classIncome = array_values(array_map(fn ($c) => [
            ...$c,
            'hours' => round($c['hours'], 2),
            'unit_price' => $rate,
            'total' => round($c['hours'] * $rate, 2),
        ], $byClass));

        return [
            'payroll' => $payroll,
            'teacher' => [
                'id' => $teacher->id,
                'code' => $teacher->code,
                'full_name' => $teacher->full_name,
                'hourly_rate' => $rate,
            ],
            'class_income' => $classIncome,
        ];
    }

    /**
     * Compute-and-upsert a teacher's payroll for one month — idempotent
     * (`unique(teacher_id, month, year)`), always reflects the current
     * computed hours; re-generating recomputes `total_hours`/`base_salary`
     * but keeps bonus/penalty unless explicitly overridden.
     */
    public function generate(int $teacherId, int $month, int $year, array $data = []): Payroll
    {
        $teacher = Teacher::findOrFail($teacherId);
        $rate = (float) ($teacher->hourly_rate ?? 0);

        $monthParam = sprintf('%04d-%02d', $year, $month);
        $summary = $this->timesheet->summary($teacherId, ['month' => $monthParam]);

        $existing = Payroll::where('teacher_id', $teacherId)->where('month', $month)->where('year', $year)->first();

        $totalHours = (float) $summary['total_hours'];
        $baseSalary = round($totalHours * $rate, 2);
        $bonus = array_key_exists('bonus', $data) ? (float) $data['bonus'] : (float) ($existing->bonus ?? 0);
        $penalty = array_key_exists('penalty', $data) ? (float) $data['penalty'] : (float) ($existing->penalty ?? 0);

        $payroll = Payroll::updateOrCreate(
            ['teacher_id' => $teacherId, 'month' => $month, 'year' => $year],
            [
                'total_hours' => $totalHours,
                'base_salary' => $baseSalary,
                'bonus' => $bonus,
                'penalty' => $penalty,
                'total_salary' => max(0, $baseSalary + $bonus - $penalty),
            ],
        );

        return $payroll;
    }
}
