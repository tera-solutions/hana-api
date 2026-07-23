<?php

namespace App\Modules\HR\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Payroll\Actions\GeneratePayrollAction;
use App\Modules\HR\Payroll\Actions\GetPayrollAction;
use App\Modules\HR\Payroll\Actions\ListPayrollAction;
use App\Modules\HR\Payroll\Actions\PayPayrollAction;
use App\Modules\HR\Payroll\Http\Requests\GeneratePayrollRequest;
use App\Modules\HR\Payroll\Http\Resources\PayrollResource;
use App\Modules\HR\Payroll\Services\PayrollService;
use Illuminate\Http\Request;

/**
 * @group HR - Payroll
 *
 * A teacher's own payroll periods — lương = giờ dạy thực tế (nguồn Timesheet) ×
 * đơn giá/giờ + thưởng − phạt. `list`/`detail` are always scoped to the acting
 * teacher. `generate` is self-service for a teacher's OWN payroll (webs/teacher
 * is the sole tenant-facing app, no separate admin portal per business) but is
 * locked down server-side: a non-admin caller can only recompute their own
 * `teacher_id`, and `bonus`/`penalty` are silently ignored for them — a teacher
 * still cannot set their own bonus/penalty, same principle as Wallet Request's
 * admin approval step. `is_admin` accounts keep full control (any teacher, any
 * bonus/penalty).
 *
 * @authenticated
 */
class PayrollController extends Controller
{
    /**
     * List payroll periods
     *
     * Defaults to the acting teacher's own periods. An `is_admin` account (the
     * center admin, running payroll for others) may pass `teacher_id` to view
     * — and, via `pay`, disburse — any teacher's payroll.
     *
     * @queryParam teacher_id integer Admin only: view another teacher's payroll. Example: 3
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function list(Request $request, PayrollService $service, ListPayrollAction $action)
    {
        $teacherId = $service->resolveListTeacherId($request->filled('teacher_id') ? $request->integer('teacher_id') : null);

        return $this->respondPaginated($action->handle($teacherId, $request->all()), PayrollResource::class);
    }

    /**
     * Payroll period detail + per-class income breakdown
     *
     * @urlParam id integer required The payroll ID. Example: 1
     */
    public function detail($id, PayrollService $service, GetPayrollAction $action)
    {
        $result = $action->handle($id);
        $service->assertOwnPayroll($result['payroll']->teacher_id);

        return $this->respondSuccess([
            'payroll' => new PayrollResource($result['payroll']),
            'teacher' => $result['teacher'],
            'class_income' => $result['class_income'],
        ]);
    }

    /**
     * Generate/recalculate payroll for one month
     *
     * Idempotent: re-running for the same teacher/month/year recomputes
     * `total_hours`/`base_salary` from Timesheet, keeping `bonus`/`penalty`
     * unless explicitly overridden in the request. Non-admin callers may only
     * target their own `teacher_id`, and any `bonus`/`penalty` they submit is
     * ignored — those fields are admin-only.
     *
     * @response 200 {"success": true, "msg": "Tính lương thành công.", "data": {"id": 1, "month": 7, "year": 2026, "total_hours": 18, "base_salary": 2700000, "bonus": 500000, "penalty": 0, "total_salary": 3200000}, "code": 200, "errors": null}
     */
    public function generate(GeneratePayrollRequest $request, PayrollService $service, GeneratePayrollAction $action)
    {
        $data = $service->authorizeGenerate($request->validated());

        $payroll = $action->handle($data['teacher_id'], $data['month'], $data['year'], $data);

        return $this->respondSuccess(new PayrollResource($payroll), 'Tính lương thành công.');
    }

    /**
     * Pay (disburse) a payroll period
     *
     * Credits the teacher's wallet with `total_salary` and marks the period
     * paid. A non-admin caller can never pay their own payroll — that's the
     * same self-dealing rule as Wallet Request's approval step.
     *
     * @urlParam id integer required The payroll ID. Example: 1
     */
    public function pay($id, PayrollService $service, GetPayrollAction $get, PayPayrollAction $action)
    {
        $result = $get->handle($id);
        $service->assertNotOwnPayroll($result['payroll']->teacher_id);

        try {
            $payroll = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new PayrollResource($payroll), 'Trả lương thành công.');
    }
}
