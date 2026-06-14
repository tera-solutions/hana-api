<?php

namespace App\Modules\Education\Enrollment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Enrollment\Actions\CancelEnrollmentAction;
use App\Modules\Education\Enrollment\Actions\CreateEnrollmentAction;
use App\Modules\Education\Enrollment\Actions\GetEnrollmentAction;
use App\Modules\Education\Enrollment\Actions\ListEnrollmentAction;
use App\Modules\Education\Enrollment\Actions\RefundEnrollmentAction;
use App\Modules\Education\Enrollment\Actions\SuspendEnrollmentAction;
use App\Modules\Education\Enrollment\Actions\TransferEnrollmentAction;
use App\Modules\Education\Enrollment\Actions\UpdateEnrollmentAction;
use App\Modules\Education\Enrollment\Http\Requests\CancelEnrollmentRequest;
use App\Modules\Education\Enrollment\Http\Requests\CreateEnrollmentRequest;
use App\Modules\Education\Enrollment\Http\Requests\SuspendEnrollmentRequest;
use App\Modules\Education\Enrollment\Http\Requests\TransferEnrollmentRequest;
use App\Modules\Education\Enrollment\Http\Requests\UpdateEnrollmentRequest;
use App\Modules\Education\Enrollment\Http\Resources\EnrollmentResource;
use Illuminate\Http\Request;

/**
 * @group Education - Enrollment
 *
 * Manage enrollments (ghi danh) — the "hợp đồng học tập" linking a student to a
 * class, its lesson package, tuition, debt, transfers and suspensions.
 *
 * @authenticated
 */
class EnrollmentController extends Controller
{
    /**
     * List enrollments
     *
     * @queryParam search string Search by enrollment code, student name, student code or phone. Example: ENR
     * @queryParam student_id integer Filter by student. Example: 1
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam class_id integer Filter by class. Example: 1
     * @queryParam sales_id integer Filter by consultant (staff user). Example: 5
     * @queryParam status string Filter by status: pending, studying, suspended, transferred, completed, cancelled, refunded. Example: studying
     * @queryParam enrolled_from date Enrolled on or after (Y-m-d). Example: 2026-07-01
     * @queryParam enrolled_to date Enrolled on or before (Y-m-d). Example: 2026-12-31
     * @queryParam has_debt boolean Filter by outstanding debt. Example: true
     * @queryParam sort_by string Sort column: code, enrolled_at, status, debt_amount, created_at. Example: enrolled_at
     * @queryParam sort_dir string asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {
     *         "id": 1, "code": "ENR-202607-00001", "status": "studying",
     *         "student_id": 1, "course_id": 1, "class_id": 1, "sales_id": 5,
     *         "enrolled_at": "2026-07-01",
     *         "total_lessons": 24, "completed_lessons": 0, "remaining_lessons": 24,
     *         "price_per_lesson": 250000, "tuition_amount": 6000000,
     *         "discount_amount": 600000, "paid_amount": 3000000, "debt_amount": 2400000
     *       }
     *     ],
     *     "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListEnrollmentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), EnrollmentResource::class);
    }

    /**
     * Enrollment detail
     *
     * Returns the enrollment plus learning progress, financial summary and the
     * payment / transfer / suspension history (enrollment.md §8).
     *
     * @urlParam id integer required The enrollment ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "enrollment": {"id": 1, "code": "ENR-202607-00001", "status": "studying"},
     *     "progress": {"total_lessons": 24, "completed_lessons": 0, "remaining_lessons": 24, "completion_rate": 0},
     *     "financial": {"tuition_amount": 6000000, "discount_amount": 600000, "paid_amount": 3000000, "debt_amount": 2400000, "refund_amount": 0},
     *     "payments": [],
     *     "transfers": [],
     *     "suspensions": []
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetEnrollmentAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'enrollment' => new EnrollmentResource($result['enrollment']),
            'progress' => $result['progress'],
            'financial' => $result['financial'],
            'payments' => $result['payments'],
            'transfers' => $result['transfers'],
            'suspensions' => $result['suspensions'],
        ]);
    }

    /**
     * Create enrollment
     *
     * Registers the student into the class and generates the invoice, payment and
     * outstanding debt (enrollment.md §7).
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Ghi danh thành công.",
     *   "data": {"id": 1, "code": "ENR-202607-00001", "status": "studying", "debt_amount": 2400000},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Business rule violation" {
     *   "success": false,
     *   "msg": "Học viên đã có ghi danh đang hoạt động ở lớp này.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateEnrollmentRequest $request, CreateEnrollmentAction $action)
    {
        try {
            $enrollment = $action->handle($request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EnrollmentResource($enrollment), 'Ghi danh thành công.');
    }

    /**
     * Update enrollment
     *
     * Partial update — only the consultant and note are editable.
     *
     * @urlParam id integer required The enrollment ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật ghi danh thành công.",
     *   "data": {"id": 1, "code": "ENR-202607-00001", "note": "Đã liên hệ phụ huynh."},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateEnrollmentRequest $request, $id, UpdateEnrollmentAction $action)
    {
        $enrollment = $action->handle($id, $request->validated());

        return $this->respondSuccess(new EnrollmentResource($enrollment), 'Cập nhật ghi danh thành công.');
    }

    /**
     * Suspend enrollment
     *
     * Reserves (bảo lưu) the enrollment. Rejected when no lessons remain (enrollment.md §9).
     *
     * @urlParam id integer required The enrollment ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Bảo lưu ghi danh thành công.",
     *   "data": {"id": 1, "status": "suspended"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="No lessons remaining" {
     *   "success": false,
     *   "msg": "Không thể bảo lưu khi không còn buổi học.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendEnrollmentRequest $request, $id, SuspendEnrollmentAction $action)
    {
        try {
            $enrollment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EnrollmentResource($enrollment), 'Bảo lưu ghi danh thành công.');
    }

    /**
     * Transfer enrollment
     *
     * Moves the student to another class of the same course (enrollment.md §10).
     *
     * @urlParam id integer required The enrollment ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Chuyển lớp thành công.",
     *   "data": {"id": 1, "class_id": 2, "status": "studying"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Different course" {
     *   "success": false,
     *   "msg": "Lớp đích phải cùng khóa học.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function transfer(TransferEnrollmentRequest $request, $id, TransferEnrollmentAction $action)
    {
        try {
            $enrollment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EnrollmentResource($enrollment), 'Chuyển lớp thành công.');
    }

    /**
     * Refund enrollment
     *
     * Refunds unused lessons (remaining × price per lesson) and closes the
     * enrollment (enrollment.md §11).
     *
     * @urlParam id integer required The enrollment ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Hoàn phí thành công.",
     *   "data": {"id": 1, "status": "refunded", "remaining_lessons": 0},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="No lessons remaining" {
     *   "success": false,
     *   "msg": "Không còn buổi học để hoàn phí.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function refund($id, RefundEnrollmentAction $action)
    {
        try {
            $enrollment = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EnrollmentResource($enrollment), 'Hoàn phí thành công.');
    }

    /**
     * Cancel enrollment
     *
     * Cancels the enrollment and removes the student from the class (enrollment.md §12).
     *
     * @urlParam id integer required The enrollment ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Hủy ghi danh thành công.",
     *   "data": {"id": 1, "status": "cancelled"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function cancel(CancelEnrollmentRequest $request, $id, CancelEnrollmentAction $action)
    {
        try {
            $enrollment = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EnrollmentResource($enrollment), 'Hủy ghi danh thành công.');
    }
}
