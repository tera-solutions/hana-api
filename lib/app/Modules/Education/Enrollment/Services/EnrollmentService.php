<?php

namespace App\Modules\Education\Enrollment\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\ClassRoom\Models\ClassStudent;
use App\Modules\Education\Course\Models\Course;
use App\Modules\Education\Enrollment\Models\Enrollment;
use App\Modules\Education\Enrollment\Models\EnrollmentSuspension;
use App\Modules\Education\Enrollment\Models\EnrollmentTransfer;
use App\Modules\Education\Student\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class EnrollmentService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['student', 'course', 'classRoom', 'classRoom.teacher', 'sales'];

    private const LOCKED_STUDENT_STATUSES = ['suspended', 'stopped', 'dropped'];

    private const TERMINAL_STATUSES = [
        Enrollment::STATUS_COMPLETED,
        Enrollment::STATUS_CANCELLED,
        Enrollment::STATUS_REFUNDED,
    ];

    /**
     * Paginated, searchable, filterable list (enrollment.md §2).
     */
    public function paginate(array $params = [])
    {
        $query = Enrollment::query();

        // Search: enrollment code, student name / code / phone (spec §2).
        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                    ->orWhereHas('student', function ($sq) use ($s) {
                        $sq->where('name', 'like', "%{$s}%")
                            ->orWhere('code', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%");
                    });
            });
        }

        foreach (['student_id', 'course_id', 'class_id', 'sales_id', 'status'] as $field) {
            if (! empty($params[$field])) {
                $query->where($field, $params[$field]);
            }
        }

        if (! empty($params['enrolled_from'])) {
            $query->whereDate('enrolled_at', '>=', $params['enrolled_from']);
        }

        if (! empty($params['enrolled_to'])) {
            $query->whereDate('enrolled_at', '<=', $params['enrolled_to']);
        }

        // "Nợ học phí" filter (spec §2).
        if (array_key_exists('has_debt', $params) && $params['has_debt'] !== '') {
            filter_var($params['has_debt'], FILTER_VALIDATE_BOOLEAN)
                ? $query->where('debt_amount', '>', 0)
                : $query->where('debt_amount', '<=', 0);
        }

        $this->applySort($query, $params, ['code', 'enrolled_at', 'status', 'debt_amount', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    public function find($id): Enrollment
    {
        return Enrollment::with(self::RELATIONS)->findOrFail($id);
    }

    /**
     * Full detail with progress, financials and history (enrollment.md §8).
     */
    public function detail($id): array
    {
        $enrollment = $this->find($id);

        return [
            'enrollment' => $enrollment,
            'progress' => $this->progress($enrollment),
            'financial' => $this->financial($enrollment),
            'payments' => $this->payments($id),
            'transfers' => EnrollmentTransfer::where('enrollment_id', $id)->latest()->get(),
            'suspensions' => EnrollmentSuspension::where('enrollment_id', $id)->latest()->get(),
        ];
    }

    /**
     * Register a student into a class (enrollment.md §5–§7).
     *
     * @throws \RuntimeException on any business-rule violation.
     */
    public function create(array $data): Enrollment
    {
        $student = Student::findOrFail($data['student_id']);
        $course = Course::findOrFail($data['course_id']);
        $class = ClassRoom::findOrFail($data['class_id']);

        $this->guardEnrollable($student, $course, $class);

        $money = $this->computeMoney($data);
        $businessId = $student->business_id ?? $class->business_id ?? $this->actingBusinessId();

        return DB::transaction(function () use ($data, $student, $course, $class, $money, $businessId) {
            $enrollment = Enrollment::create([
                'business_id' => $businessId,
                'student_id' => $student->id,
                'course_id' => $course->id,
                'class_id' => $class->id,
                'sales_id' => $data['sales_id'] ?? null,
                'enrolled_at' => $data['enrolled_at'] ?? now()->toDateString(),
                'total_lessons' => $money['total_lessons'],
                'completed_lessons' => 0,
                'remaining_lessons' => $money['total_lessons'] + $money['bonus_lessons'],
                'price_per_lesson' => $money['price_per_lesson'],
                'tuition_amount' => $money['tuition_amount'],
                'discount_amount' => $money['discount_amount'],
                'paid_amount' => $money['paid_amount'],
                'debt_amount' => $money['debt_amount'],
                'status' => Enrollment::STATUS_STUDYING,
                'note' => $data['note'] ?? null,
            ]);

            $enrollment->update(['code' => $this->makeCode('ENR', $enrollment->id)]);

            $this->assignToClass($enrollment, $student->id, $class->id);
            $this->generateBilling($enrollment, $student->id, $businessId, $money, $data['payment_method'] ?? 'cash');
            $this->logHistory($enrollment, 'enrolled', $businessId, $student->id, toClassId: $class->id);

            return $this->find($enrollment->id);
        });
    }

    /**
     * Update editable fields (enrollment.md §13 PUT). Identity, package and
     * financial snapshots are immutable post-creation.
     */
    public function update($id, array $data): Enrollment
    {
        $enrollment = $this->find($id);

        $enrollment->fill(array_filter([
            'sales_id' => $data['sales_id'] ?? null,
            'note' => $data['note'] ?? null,
        ], fn ($v) => $v !== null));

        $enrollment->save();

        return $this->find($id);
    }

    /**
     * Suspend (bảo lưu) an enrollment (enrollment.md §9).
     *
     * @throws \RuntimeException
     */
    public function suspend($id, array $data): Enrollment
    {
        $enrollment = $this->find($id);

        if ($enrollment->status === Enrollment::STATUS_SUSPENDED) {
            throw new \RuntimeException('Ghi danh đang ở trạng thái bảo lưu.');
        }

        if (in_array($enrollment->status, self::TERMINAL_STATUSES, true)) {
            throw new \RuntimeException('Không thể bảo lưu ghi danh đã kết thúc.');
        }

        if ((int) $enrollment->remaining_lessons <= 0) {
            throw new \RuntimeException('Không thể bảo lưu khi không còn buổi học.');
        }

        return DB::transaction(function () use ($enrollment, $data) {
            EnrollmentSuspension::create([
                'enrollment_id' => $enrollment->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'],
            ]);

            $enrollment->update(['status' => Enrollment::STATUS_SUSPENDED]);

            return $this->find($enrollment->id);
        });
    }

    /**
     * Transfer to another class (enrollment.md §10).
     *
     * @throws \RuntimeException
     */
    public function transfer($id, array $data): Enrollment
    {
        $enrollment = $this->find($id);

        if (in_array($enrollment->status, self::TERMINAL_STATUSES, true)) {
            throw new \RuntimeException('Không thể chuyển lớp cho ghi danh đã kết thúc.');
        }

        $target = ClassRoom::findOrFail($data['to_class_id']);
        $fromClassId = $enrollment->class_id;

        if ($target->id === $fromClassId) {
            throw new \RuntimeException('Lớp đích phải khác lớp hiện tại.');
        }

        if ((int) $target->course_id !== (int) $enrollment->course_id) {
            throw new \RuntimeException('Lớp đích phải cùng khóa học.');
        }

        $this->guardClassActive($target);
        $this->guardClassCapacity($target);

        return DB::transaction(function () use ($enrollment, $target, $fromClassId, $data) {
            ClassStudent::where('class_id', $fromClassId)
                ->where('student_id', $enrollment->student_id)
                ->update(['status' => ClassStudent::STATUS_TRANSFERRED_OUT]);

            $this->assignToClass($enrollment, $enrollment->student_id, $target->id);

            $enrollment->update(['class_id' => $target->id]);

            EnrollmentTransfer::create([
                'enrollment_id' => $enrollment->id,
                'from_class_id' => $fromClassId,
                'to_class_id' => $target->id,
                'transfer_date' => $data['transfer_date'],
                'reason' => $data['reason'] ?? null,
            ]);

            $this->logHistory($enrollment, 'transferred', $enrollment->business_id, $enrollment->student_id, $fromClassId, $target->id);

            return $this->find($enrollment->id);
        });
    }

    /**
     * Refund unused lessons and close the enrollment (enrollment.md §11).
     *
     * @throws \RuntimeException
     */
    public function refund($id): Enrollment
    {
        $enrollment = $this->find($id);

        if (in_array($enrollment->status, [Enrollment::STATUS_REFUNDED, Enrollment::STATUS_CANCELLED], true)) {
            throw new \RuntimeException('Ghi danh đã đóng, không thể hoàn phí.');
        }

        if ((int) $enrollment->remaining_lessons <= 0) {
            throw new \RuntimeException('Không còn buổi học để hoàn phí.');
        }

        $amount = round((int) $enrollment->remaining_lessons * (float) $enrollment->price_per_lesson, 2);

        return DB::transaction(function () use ($enrollment, $amount) {
            $invoiceId = $this->guard(fn () => DB::table('fin_invoices')
                ->where('enrollment_id', $enrollment->id)
                ->orderByDesc('id')
                ->value('id'));

            if ($invoiceId) {
                $this->guard(fn () => DB::table('fin_refunds')->insert([
                    'business_id' => $enrollment->business_id,
                    'invoice_id' => $invoiceId,
                    'student_id' => $enrollment->student_id,
                    'amount' => $amount,
                    'reason' => 'Hoàn phí ghi danh #'.$enrollment->code,
                    'status' => 'pending',
                    'refunded_at' => now(),
                    'created_by' => $this->actingUserId(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            $enrollment->update([
                'status' => Enrollment::STATUS_REFUNDED,
                'remaining_lessons' => 0,
            ]);

            $this->logHistory($enrollment, 'refunded', $enrollment->business_id, $enrollment->student_id);

            return $this->find($enrollment->id);
        });
    }

    /**
     * Cancel an enrollment and remove the student from the class (enrollment.md §12).
     *
     * @throws \RuntimeException
     */
    public function cancel($id, array $data = []): Enrollment
    {
        $enrollment = $this->find($id);

        if (in_array($enrollment->status, [Enrollment::STATUS_CANCELLED, Enrollment::STATUS_REFUNDED, Enrollment::STATUS_COMPLETED], true)) {
            throw new \RuntimeException('Ghi danh đã đóng, không thể hủy.');
        }

        return DB::transaction(function () use ($enrollment, $data) {
            ClassStudent::where('class_id', $enrollment->class_id)
                ->where('student_id', $enrollment->student_id)
                ->update(['status' => ClassStudent::STATUS_DROPPED]);

            $enrollment->update([
                'status' => Enrollment::STATUS_CANCELLED,
                'note' => $data['reason'] ?? $enrollment->note,
            ]);

            $this->logHistory($enrollment, 'cancelled', $enrollment->business_id, $enrollment->student_id);

            return $this->find($enrollment->id);
        });
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function guardEnrollable(Student $student, Course $course, ClassRoom $class): void
    {
        if (in_array($student->status, self::LOCKED_STUDENT_STATUSES, true)) {
            throw new \RuntimeException('Học viên đang bị khóa, không thể ghi danh.');
        }

        if (! $course->is_active) {
            throw new \RuntimeException('Khóa học đang ngừng hoạt động.');
        }

        if ((int) $class->course_id !== (int) $course->id) {
            throw new \RuntimeException('Lớp học không thuộc khóa học đã chọn.');
        }

        $this->guardClassActive($class);
        $this->guardClassCapacity($class);

        $duplicate = Enrollment::where('student_id', $student->id)
            ->where('class_id', $class->id)
            ->whereIn('status', Enrollment::ACTIVE_STATUSES)
            ->exists();

        if ($duplicate) {
            throw new \RuntimeException('Học viên đã có ghi danh đang hoạt động ở lớp này.');
        }
    }

    private function guardClassActive(ClassRoom $class): void
    {
        if (in_array($class->status, [ClassRoom::STATUS_SUSPENDED, ClassRoom::STATUS_COMPLETED, ClassRoom::STATUS_DRAFT], true)) {
            throw new \RuntimeException('Lớp học không ở trạng thái hoạt động.');
        }
    }

    private function guardClassCapacity(ClassRoom $class): void
    {
        if ($class->max_capacity === null) {
            return;
        }

        $current = ClassStudent::where('class_id', $class->id)
            ->where('status', ClassStudent::STATUS_ACTIVE)
            ->count();

        if ($current >= (int) $class->max_capacity) {
            throw new \RuntimeException('Lớp học đã đạt sĩ số tối đa.');
        }
    }

    // ── Money ─────────────────────────────────────────────────────────────────

    /**
     * Resolve the lesson package + promotions into the financial snapshot
     * (enrollment.md §5).
     *
     * @return array{total_lessons:int, bonus_lessons:int, price_per_lesson:float, tuition_amount:float, discount_amount:float, paid_amount:float, debt_amount:float}
     */
    private function computeMoney(array $data): array
    {
        $totalLessons = (int) $data['total_lessons'];
        $bonusLessons = (int) ($data['bonus_lessons'] ?? 0);
        $price = (float) $data['price_per_lesson'];

        $tuition = round($totalLessons * $price, 2);

        $percent = (float) ($data['discount_percent'] ?? 0);
        $direct = (float) ($data['discount_amount'] ?? 0);
        $discount = min($tuition, round($tuition * $percent / 100, 2) + $direct);

        $payable = max(0, round($tuition - $discount, 2));
        $paid = min($payable, max(0, (float) ($data['paid_amount'] ?? 0)));
        $debt = round($payable - $paid, 2);

        return [
            'total_lessons' => $totalLessons,
            'bonus_lessons' => $bonusLessons,
            'price_per_lesson' => $price,
            'tuition_amount' => $tuition,
            'discount_amount' => $discount,
            'paid_amount' => $paid,
            'debt_amount' => $debt,
        ];
    }

    // ── Side effects ────────────────────────────────────────────────────────────

    private function assignToClass(Enrollment $enrollment, $studentId, $classId): void
    {
        $existing = ClassStudent::where('class_id', $classId)
            ->where('student_id', $studentId)
            ->first();

        if ($existing) {
            $existing->update([
                'status' => ClassStudent::STATUS_ACTIVE,
                'enrolled_at' => $enrollment->enrolled_at,
            ]);

            return;
        }

        ClassStudent::create([
            'class_id' => $classId,
            'student_id' => $studentId,
            'status' => ClassStudent::STATUS_ACTIVE,
            'enrolled_at' => $enrollment->enrolled_at,
        ]);
    }

    /**
     * Generate the invoice, optional payment and outstanding debt (enrollment.md §7).
     * Guarded so an incomplete finance schema never aborts the enrollment itself.
     *
     * @param  array{tuition_amount:float, discount_amount:float, paid_amount:float, debt_amount:float}  $money
     */
    private function generateBilling(Enrollment $enrollment, $studentId, $businessId, array $money, string $method): void
    {
        if ($businessId === null) {
            return;
        }

        $payable = round($money['tuition_amount'] - $money['discount_amount'], 2);
        $fullyPaid = $money['debt_amount'] <= 0;

        $invoiceId = $this->guard(fn () => DB::table('fin_invoices')->insertGetId([
            'business_id' => $businessId,
            'student_id' => $studentId,
            'enrollment_id' => $enrollment->id,
            'code' => $this->makeCode('INV', $enrollment->id),
            'subtotal' => $money['tuition_amount'],
            'discount' => $money['discount_amount'],
            'total' => $payable,
            'status' => $fullyPaid ? 'paid' : ($money['paid_amount'] > 0 ? 'partial' : 'pending'),
            'paid_at' => $fullyPaid ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        if ($money['paid_amount'] > 0) {
            $this->guard(fn () => DB::table('fin_payments')->insert([
                'business_id' => $businessId,
                'student_id' => $studentId,
                'enrollment_id' => $enrollment->id,
                'invoice_id' => $invoiceId ?: null,
                'amount' => $money['paid_amount'],
                'method' => $method,
                'status' => 'completed',
                'paid_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        if ($money['debt_amount'] > 0) {
            $this->guard(fn () => DB::table('fin_debts')->insert([
                'business_id' => $businessId,
                'student_id' => $studentId,
                'invoice_id' => $invoiceId ?: null,
                'amount' => $payable,
                'paid_amount' => $money['paid_amount'],
                'remaining_amount' => $money['debt_amount'],
                'status' => $money['paid_amount'] > 0 ? 'partial' : 'unpaid',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    private function logHistory(Enrollment $enrollment, string $action, $businessId, $studentId, $fromClassId = null, $toClassId = null): void
    {
        if ($businessId === null) {
            return;
        }

        $this->guard(fn () => DB::table('edu_enrollment_histories')->insert([
            'business_id' => $businessId,
            'student_id' => $studentId,
            'enrollment_id' => $enrollment->id,
            'from_class_id' => $fromClassId,
            'to_class_id' => $toClassId,
            'action' => $action,
            'created_by' => $this->actingUserId(),
            'effective_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    // ── Detail helpers ──────────────────────────────────────────────────────────

    private function progress(Enrollment $enrollment): array
    {
        $total = (int) $enrollment->total_lessons;
        $completed = (int) $enrollment->completed_lessons;

        return [
            'total_lessons' => $total,
            'completed_lessons' => $completed,
            'remaining_lessons' => (int) $enrollment->remaining_lessons,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
        ];
    }

    private function financial(Enrollment $enrollment): array
    {
        $refunds = $this->guard(fn () => (float) DB::table('fin_refunds')
            ->join('fin_invoices', 'fin_refunds.invoice_id', '=', 'fin_invoices.id')
            ->where('fin_invoices.enrollment_id', $enrollment->id)
            ->sum('fin_refunds.amount'));

        return [
            'tuition_amount' => (float) $enrollment->tuition_amount,
            'discount_amount' => (float) $enrollment->discount_amount,
            'paid_amount' => (float) $enrollment->paid_amount,
            'debt_amount' => (float) $enrollment->debt_amount,
            'refund_amount' => is_numeric($refunds) ? (float) $refunds : 0.0,
        ];
    }

    private function payments($enrollmentId): array
    {
        $rows = $this->guard(fn () => DB::table('fin_payments')
            ->where('enrollment_id', $enrollmentId)
            ->orderByDesc('id')
            ->get());

        return is_iterable($rows) ? collect($rows)->toArray() : [];
    }

    // ── Misc ──────────────────────────────────────────────────────────────────

    private function makeCode(string $prefix, int $id): string
    {
        return sprintf('%s-%s-%05d', $prefix, now()->format('Ym'), $id);
    }

    private function actingUserId(): int|string|null
    {
        return Auth::guard('api')->id() ?? Auth::id();
    }

    private function actingBusinessId(): ?int
    {
        $user = Auth::guard('api')->user() ?? Auth::user();

        return $user?->business_id;
    }

    /**
     * Execute a query, treating any DB error as null (missing/partial schema).
     */
    private function guard(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return null;
        }
    }
}
