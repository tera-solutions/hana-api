<?php

namespace App\Modules\Education\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Student\Actions\CreateStudentAction;
use App\Modules\Education\Student\Actions\DeleteStudentAction;
use App\Modules\Education\Student\Actions\ExportStudentAction;
use App\Modules\Education\Student\Actions\GetStudentAction;
use App\Modules\Education\Student\Actions\ListStudentAction;
use App\Modules\Education\Student\Actions\RestoreStudentAction;
use App\Modules\Education\Student\Actions\SuspendStudentAction;
use App\Modules\Education\Student\Actions\UpdateStudentAction;
use App\Modules\Education\Student\Http\Requests\CreateStudentRequest;
use App\Modules\Education\Student\Http\Requests\ExportStudentRequest;
use App\Modules\Education\Student\Http\Requests\SuspendStudentRequest;
use App\Modules\Education\Student\Http\Requests\UpdateStudentRequest;
use App\Modules\Education\Student\Http\Resources\StudentResource;
use Illuminate\Http\Request;

/**
 * @group Education - Student
 *
 * Manage students (enrollment lifecycle).
 *
 * @authenticated
 */
class StudentController extends Controller
{
    /**
     * List students
     *
     * @queryParam search string Search by code, name, email, phone or parent name. Example: alice
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam level string Filter by level. Example: A1
     * @queryParam status string Filter by status. Example: active
     * @queryParam enrolled_from date Enrolled on or after (Y-m-d). Example: 2026-01-01
     * @queryParam enrolled_to date Enrolled on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: code, name, enrollment_date, created_at. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "code": "STU001", "name": "Nguyễn Văn A", "level_id": 1, "status": "active", "email": "a@example.com", "phone": "0900000000", "business_id": 1, "branch_id": 1}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListStudentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), StudentResource::class);
    }

    /**
     * Student detail
     *
     * @urlParam id integer required The student ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "student": {"id": 1, "code": "STU001", "name": "Nguyễn Văn A", "level_id": 1, "status": "active", "business_id": 1, "branch_id": 1},
     *     "statistics": {"classes": 2, "attendance_rate": 0.9}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetStudentAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'student' => new StudentResource($result['student']),
            'statistics' => $result['statistics'],
        ]);
    }

    /**
     * Create student
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo học viên thành công.",
     *   "data": {"id": 1, "code": "STU001", "name": "Nguyễn Văn A", "level_id": 1, "status": "active", "business_id": 1, "branch_id": 1},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateStudentRequest $request, CreateStudentAction $action)
    {
        $student = $action->handle($request->validated());

        return $this->respondSuccess(new StudentResource($student), 'Tạo học viên thành công.');
    }

    /**
     * Update student
     *
     * @urlParam id integer required The student ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật học viên thành công.",
     *   "data": {"id": 1, "code": "STU001", "name": "Nguyễn Văn A", "level_id": 1, "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateStudentRequest $request, $id, UpdateStudentAction $action)
    {
        $student = $action->handle($id, $request->validated());

        return $this->respondSuccess(new StudentResource($student), 'Cập nhật học viên thành công.');
    }

    /**
     * Suspend student
     *
     * @urlParam id integer required The student ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Ngừng học viên thành công.",
     *   "data": {"id": 1, "code": "STU001", "name": "Nguyễn Văn A", "status": "suspended"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendStudentRequest $request, $id, SuspendStudentAction $action)
    {
        try {
            $student = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new StudentResource($student), 'Ngừng học viên thành công.');
    }

    /**
     * Restore student
     *
     * @urlParam id integer required The student ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục học viên thành công.",
     *   "data": {"id": 1, "code": "STU001", "name": "Nguyễn Văn A", "status": "active"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function restore($id, RestoreStudentAction $action)
    {
        try {
            $student = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new StudentResource($student), 'Khôi phục học viên thành công.');
    }

    /**
     * Delete student
     *
     * @urlParam id integer required The student ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa học viên thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($id, DeleteStudentAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa học viên thành công.');
    }

    /**
     * Export students
     *
     * Returns a link / payload for the generated export file.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"url": "http://localhost/hana-api/storage/exports/students-2026-06-12.xlsx"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function export(ExportStudentRequest $request, ExportStudentAction $action)
    {
        return $this->respondSuccess($action->handle($request->validated()));
    }
}
