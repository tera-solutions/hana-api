<?php

namespace App\Modules\HR\Payroll\Services;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Finance\Wallet\Models\Wallet;
use App\Modules\Finance\Wallet\Services\WalletService;
use App\Modules\HR\Teacher\Models\Payroll;
use App\Modules\HR\Teacher\Models\Teacher;
use App\Modules\HR\Timesheet\Services\TimesheetService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;
use Package\Exception\AuthorizationException;

/**
 * Lương giáo viên = giờ dạy thực tế (nguồn `TimesheetService`, buổi đã điểm
 * danh) × đơn giá/giờ (`hr_teachers.hourly_rate`) + thưởng − phạt (project
 * decision 2026-07-17). Không có cổng thanh toán: `generate()` chỉ TÍNH và ghi
 * lại một dòng `hr_payrolls`, việc chi trả nằm ngoài hệ thống. `paginate()` tự
 * backfill mọi tháng còn thiếu qua `ensureGenerated()` — teacher/FE không cần
 * bấm "Tính lương" thủ công, trang payroll đọc như mọi trang danh sách khác.
 * `bonus`/`penalty` vẫn admin-only khi set qua request (PayrollController),
 * cùng nguyên tắc với Wallet Request (không để giáo viên tự đặt thưởng/phạt
 * cho chính mình) — backfill tự động luôn dùng bonus/penalty mặc định 0.
 */
class PayrollService
{
    use HandlesEntityQueries;

    public function __construct(
        private readonly TimesheetService $timesheet,
        private readonly WalletService $wallets,
    ) {}

    public function paginate(?int $teacherId, array $params = [])
    {
        // Null means the caller has no teacher profile to scope to (e.g. the
        // tenant owner) — not an error, just nothing to list.
        if (! $teacherId) {
            return new LengthAwarePaginator([], 0, $this->resolvePerPage($params));
        }

        $this->ensureGenerated($teacherId);

        $query = Payroll::query()->where('teacher_id', $teacherId);

        $this->applySort($query, $params, ['year', 'month', 'total_salary']);
        if (empty($params['sort_by'])) {
            $query->orderByDesc('year')->orderByDesc('month');
        }

        return $query->paginate($this->resolvePerPage($params));
    }

    /**
     * Which teacher `list()` should scope to: an `is_admin` caller may view
     * another teacher via `$requestedTeacherId`, everyone else is locked to
     * their own acting teacher (null when they have no teacher profile).
     */
    public function resolveListTeacherId(?int $requestedTeacherId): ?int
    {
        if ($requestedTeacherId && $this->isAdmin()) {
            return $requestedTeacherId;
        }

        return $this->actingTeacherId();
    }

    /**
     * @throws AuthorizationException
     */
    public function assertOwnPayroll(int $payrollTeacherId): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if ($this->actingTeacherId() !== $payrollTeacherId) {
            throw new AuthorizationException('Bạn chỉ có thể xem bảng lương của chính mình.');
        }
    }

    /**
     * @throws AuthorizationException
     */
    public function assertNotOwnPayroll(int $payrollTeacherId): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if ($this->actingTeacherId() === $payrollTeacherId) {
            throw new AuthorizationException('Bạn không thể tự trả lương cho chính mình.');
        }
    }

    /**
     * Non-admin callers may only generate for their OWN `teacher_id`, and any
     * `bonus`/`penalty` they submit is silently ignored — those fields are
     * admin-only, same principle as Wallet Request's approval step.
     *
     * @throws AuthorizationException
     */
    public function authorizeGenerate(array $data): array
    {
        if ($this->isAdmin()) {
            return $data;
        }

        $teacherId = $this->actingTeacherId();

        if (! $teacherId) {
            throw new AuthorizationException('Bạn chưa được gán hồ sơ giáo viên.');
        }

        if ((int) $data['teacher_id'] !== $teacherId) {
            throw new AuthorizationException('Bạn chỉ có thể tính lương cho chính mình.');
        }

        unset($data['bonus'], $data['penalty']);

        return $data;
    }

    private function isAdmin(): bool
    {
        $user = Auth::guard('api')->user();

        return (bool) ($user && $user->is_admin);
    }

    private function actingTeacherId(): ?int
    {
        $userId = Auth::guard('api')->id();

        return $userId ? Teacher::where('user_id', $userId)->value('id') : null;
    }

    /**
     * Backfill any month, from the teacher's first worked session through the
     * current month, that doesn't have a `hr_payrolls` row yet. Idempotent and
     * cheap once caught up — only ever generates the months actually missing.
     */
    public function ensureGenerated(int $teacherId): void
    {
        $earliest = ClassSession::query()
            ->where(function ($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId)->orWhere('substitute_teacher_id', $teacherId);
            })
            ->whereHas('attendances')
            ->orderBy('session_date')
            ->value('session_date');

        if (! $earliest) {
            return;
        }

        $existing = Payroll::where('teacher_id', $teacherId)
            ->get(['month', 'year'])
            ->map(fn ($p) => sprintf('%04d-%02d', $p->year, $p->month))
            ->flip();

        $cursor = Carbon::parse($earliest)->startOfMonth();
        $end = now()->startOfMonth();

        while ($cursor->lte($end)) {
            if (! isset($existing[$cursor->format('Y-m')])) {
                $this->generate($teacherId, $cursor->month, $cursor->year);
            }
            $cursor->addMonth();
        }
    }

    public function find(int $id): Payroll
    {
        return Payroll::findOrFail($id);
    }

    /**
     * Dashboard stats for the payroll list screen — tenant-scoped like every
     * other query here (Teacher/Wallet/Payroll all `use BelongsToBusiness`).
     *
     * @return array{teachers: int, total_balance: float, pending: int}
     */
    public function summary(): array
    {
        $teachers = Teacher::where('status', Teacher::STATUS_ACTIVE)->count();

        $totalBalance = (float) Wallet::where('owner_type', Wallet::OWNER_TEACHER)
            ->selectRaw('COALESCE(SUM(available_balance + bonus_balance), 0) as total')
            ->value('total');

        $pending = Payroll::where('status', Payroll::STATUS_DRAFT)
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->count();

        return ['teachers' => $teachers, 'total_balance' => $totalBalance, 'pending' => $pending];
    }

    /**
     * The teacher's wallet (id + spendable balance) — lets the FE call
     * `/wallet/adjustment` and `/wallet/transactions` directly for the
     * "Cập nhật số dư" flow without a separate payroll-specific endpoint.
     */
    public function walletFor(int $teacherId): ?Wallet
    {
        $teacher = Teacher::find($teacherId);
        if (! $teacher) {
            return null;
        }

        return $this->wallets->createForOwner($teacher->business_id, Wallet::OWNER_TEACHER, $teacher->id);
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
     * Recompute every existing payroll row for a teacher against their current
     * `hourly_rate` — call this when the rate changes so already-generated
     * months (which `ensureGenerated()` won't touch again, by design) don't
     * stay stale. Preserves each row's bonus/penalty, same as any `generate()`
     * call with no explicit override.
     */
    public function regenerateForTeacher(int $teacherId): void
    {
        Payroll::where('teacher_id', $teacherId)
            ->get(['month', 'year'])
            ->each(fn (Payroll $p) => $this->generate($teacherId, $p->month, $p->year));
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

    /**
     * Actually disburse a payroll period: credits the teacher's wallet with
     * `total_salary` and marks the period paid. The only place money moves —
     * `generate()` only ever computes amounts.
     *
     * @throws \RuntimeException when already paid or nothing to pay
     */
    public function pay(int $id): Payroll
    {
        return DB::transaction(function () use ($id) {
            $payroll = Payroll::lockForUpdate()->findOrFail($id);

            if ($payroll->status === Payroll::STATUS_PAID) {
                throw new \RuntimeException('Bảng lương này đã được trả.');
            }

            if ((float) $payroll->total_salary <= 0) {
                throw new \RuntimeException('Không có lương để trả cho kỳ này.');
            }

            $teacher = Teacher::findOrFail($payroll->teacher_id);
            $wallet = $this->wallets->createForOwner($teacher->business_id, Wallet::OWNER_TEACHER, $teacher->id);

            $this->wallets->recordFromPayroll([
                'wallet_id' => $wallet->id,
                'amount' => (float) $payroll->total_salary,
                'payroll_id' => $payroll->id,
                'note' => sprintf('Trả lương tháng %02d/%d', $payroll->month, $payroll->year),
            ]);

            $payroll->update(['status' => Payroll::STATUS_PAID, 'paid_at' => now()]);

            return $payroll->fresh();
        });
    }
}
