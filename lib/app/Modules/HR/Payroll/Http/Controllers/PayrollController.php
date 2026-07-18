<?php

namespace App\Modules\HR\Payroll\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Payroll\Actions\GeneratePayrollAction;
use App\Modules\HR\Payroll\Actions\GetPayrollAction;
use App\Modules\HR\Payroll\Actions\ListPayrollAction;
use App\Modules\HR\Payroll\Http\Requests\GeneratePayrollRequest;
use App\Modules\HR\Payroll\Http\Resources\PayrollResource;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Package\Exception\AuthorizationException;

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
     * List my payroll periods
     *
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     */
    public function list(Request $request, ListPayrollAction $action)
    {
        $teacherId = $this->actingTeacherId();

        if (! $teacherId) {
            return $this->respondSuccess([
                'items' => [],
                'pagination' => ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'last_page' => 1],
            ]);
        }

        return $this->respondPaginated($action->handle($teacherId, $request->all()), PayrollResource::class);
    }

    /**
     * Payroll period detail + per-class income breakdown
     *
     * @urlParam id integer required The payroll ID. Example: 1
     */
    public function detail($id, GetPayrollAction $action)
    {
        $result = $action->handle($id);
        $this->assertOwnPayroll($result['payroll']->teacher_id);

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
    public function generate(GeneratePayrollRequest $request, GeneratePayrollAction $action)
    {
        $data = $request->validated();
        $user = Auth::guard('api')->user();

        if (! $user || ! $user->is_admin) {
            $teacherId = $this->actingTeacherId();

            if (! $teacherId) {
                throw new AuthorizationException('Bạn chưa được gán hồ sơ giáo viên.');
            }

            if ((int) $data['teacher_id'] !== $teacherId) {
                throw new AuthorizationException('Bạn chỉ có thể tính lương cho chính mình.');
            }

            unset($data['bonus'], $data['penalty']);
        }

        $payroll = $action->handle($data['teacher_id'], $data['month'], $data['year'], $data);

        return $this->respondSuccess(new PayrollResource($payroll), 'Tính lương thành công.');
    }

    private function actingTeacherId(): ?int
    {
        $userId = Auth::guard('api')->id();

        return $userId ? Teacher::where('user_id', $userId)->value('id') : null;
    }

    /**
     * @throws AuthorizationException
     */
    private function assertOwnPayroll(int $payrollTeacherId): void
    {
        $user = Auth::guard('api')->user();

        if (! $user || $user->is_admin) {
            return;
        }

        if ($this->actingTeacherId() !== $payrollTeacherId) {
            throw new AuthorizationException('Bạn chỉ có thể xem bảng lương của chính mình.');
        }
    }
}
