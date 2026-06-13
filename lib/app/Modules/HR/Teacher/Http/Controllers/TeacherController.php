<?php

namespace App\Modules\HR\Teacher\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Teacher\Actions\CreateTeacherAction;
use App\Modules\HR\Teacher\Actions\GetTeacherAction;
use App\Modules\HR\Teacher\Actions\ListTeacherAction;
use App\Modules\HR\Teacher\Actions\ResignTeacherAction;
use App\Modules\HR\Teacher\Actions\RestoreTeacherAction;
use App\Modules\HR\Teacher\Actions\SuspendTeacherAction;
use App\Modules\HR\Teacher\Actions\UpdateTeacherAction;
use App\Modules\HR\Teacher\Http\Requests\CreateTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\ResignTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\SuspendTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\UpdateTeacherRequest;
use App\Modules\HR\Teacher\Http\Resources\TeacherResource;
use Illuminate\Http\Request;

/**
 * @group HR - Teacher
 *
 * Manage teachers (full lifecycle, specialisations, certificates).
 *
 * @authenticated
 */
class TeacherController extends Controller
{
    /**
     * List teachers
     *
     * @queryParam search string Search by code, full name, email or phone. Example: jane
     * @queryParam status string Filter by status: active, suspended, resigned. Example: active
     * @queryParam teacher_type string Filter by type: full_time, part_time, freelancer, assistant. Example: full_time
     * @queryParam employment_type string Filter by employment type. Example: contract
     * @queryParam branch_id integer Filter by branch id. Example: 1
     * @queryParam manager_id integer Filter by manager user id. Example: 1
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam skill string Filter by skill name (one or many). Example: IELTS
     * @queryParam joined_from date Joined on or after (Y-m-d). Example: 2026-01-01
     * @queryParam joined_to date Joined on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: code, full_name, joined_at, created_at, status. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {
     *         "id": 1, "code": "TCH001", "full_name": "Jane Doe", "avatar": null,
     *         "gender": "female", "dob": "1990-05-12", "email": "jane@hana.edu.vn", "phone": "0901234567",
     *         "identity_no": null, "address": null,
     *         "teacher_type": "full_time", "employment_type": "contract",
     *         "hourly_rate": 150000, "monthly_salary": null,
     *         "status": "active", "joined_at": "2026-01-10", "resigned_at": null, "note": null,
     *         "business_id": 1, "branch_id": 1, "branch": {"id": 1, "name": "Hà Nội"},
     *         "manager_id": null, "skills": [{"id": 1, "skill_name": "IELTS", "level": "expert"}],
     *         "created_by": 1, "updated_by": null, "deleted_by": null,
     *         "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-01T08:00:00.000000Z", "deleted_at": null
     *       }
     *     ],
     *     "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListTeacherAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), TeacherResource::class);
    }

    /**
     * Teacher detail
     *
     * Returns the full teacher record with all relations and operational statistics.
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "teacher": {
     *       "id": 1, "code": "TCH001", "full_name": "Jane Doe", "avatar": null,
     *       "gender": "female", "dob": "1990-05-12", "email": "jane@hana.edu.vn", "phone": "0901234567",
     *       "identity_no": null, "address": null,
     *       "teacher_type": "full_time", "employment_type": "contract",
     *       "hourly_rate": 150000, "monthly_salary": null,
     *       "status": "active", "joined_at": "2026-01-10", "resigned_at": null, "note": null,
     *       "business_id": 1, "branch_id": 1, "branch": {"id": 1, "name": "Hà Nội"},
     *       "manager_id": null, "manager": null,
     *       "skills": [{"id": 1, "skill_name": "IELTS", "level": "expert"}],
     *       "certificates": [{"id": 1, "teacher_id": 1, "certificate_name": "IELTS 8.0", "issuer": "British Council", "issued_date": "2024-03-01", "expired_date": "2027-03-01", "attachment": null, "is_expired": false, "is_expiring_soon": false}],
     *       "created_by": 1, "updated_by": null, "deleted_by": null,
     *       "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-01T08:00:00.000000Z", "deleted_at": null
     *     },
     *     "statistics": {
     *       "total_classes": 4, "total_sessions": 32, "total_contracts": 1,
     *       "total_payrolls": 5, "total_reviews": 10, "average_rating": 0
     *     }
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetTeacherAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'teacher' => new TeacherResource($result['teacher']),
            'statistics' => $result['statistics'],
        ]);
    }

    /**
     * Create teacher
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo giáo viên thành công.",
     *   "data": {
     *     "id": 1, "code": "TCH001", "full_name": "Jane Doe", "avatar": null,
     *     "gender": "female", "dob": "1990-05-12", "email": "jane@hana.edu.vn", "phone": "0901234567",
     *     "teacher_type": "full_time", "employment_type": "contract",
     *     "hourly_rate": 150000, "monthly_salary": null,
     *     "status": "active", "joined_at": "2026-01-10", "resigned_at": null, "note": null,
     *     "business_id": 1, "branch_id": 1,
     *     "skills": [{"id": 1, "skill_name": "IELTS", "level": "expert"}],
     *     "certificates": [],
     *     "created_by": 1, "updated_by": null, "deleted_by": null,
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T08:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateTeacherRequest $request, CreateTeacherAction $action)
    {
        $teacher = $action->handle($request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Tạo giáo viên thành công.');
    }

    /**
     * Update teacher
     *
     * Partial update — only send fields to change. Code and status are immutable.
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật giáo viên thành công.",
     *   "data": {
     *     "id": 1, "code": "TCH001", "full_name": "Jane Doe",
     *     "teacher_type": "part_time", "employment_type": "contract",
     *     "hourly_rate": 200000, "monthly_salary": null,
     *     "status": "active", "joined_at": "2026-01-10",
     *     "skills": [{"id": 2, "skill_name": "TOEIC", "level": "intermediate"}],
     *     "created_by": 1, "updated_by": 1, "deleted_by": null,
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T09:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateTeacherRequest $request, $id, UpdateTeacherAction $action)
    {
        $teacher = $action->handle($id, $request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Cập nhật giáo viên thành công.');
    }

    /**
     * Suspend teacher
     *
     * Moves a teacher to "suspended" status and records the reason.
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Ngừng giáo viên thành công.",
     *   "data": {"id": 1, "code": "TCH001", "full_name": "Jane Doe", "status": "suspended",
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T09:00:00.000000Z", "deleted_at": null},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Already suspended" {
     *   "success": false,
     *   "msg": "Giáo viên đang ở trạng thái tạm ngừng.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendTeacherRequest $request, $id, SuspendTeacherAction $action)
    {
        try {
            $teacher = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new TeacherResource($teacher), 'Ngừng giáo viên thành công.');
    }

    /**
     * Restore teacher
     *
     * Reactivates a suspended teacher back to "active".
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục giáo viên thành công.",
     *   "data": {"id": 1, "code": "TCH001", "full_name": "Jane Doe", "status": "active",
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T10:00:00.000000Z", "deleted_at": null},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Not suspended" {
     *   "success": false,
     *   "msg": "Chỉ có thể khôi phục giáo viên đang tạm ngừng.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function restore($id, RestoreTeacherAction $action)
    {
        try {
            $teacher = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new TeacherResource($teacher), 'Khôi phục giáo viên thành công.');
    }

    /**
     * Resign teacher
     *
     * Marks a teacher as resigned. Blocked while they still hold active classes.
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cho giáo viên nghỉ việc thành công.",
     *   "data": {"id": 1, "code": "TCH001", "full_name": "Jane Doe", "status": "resigned", "resigned_at": "2026-06-30",
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T10:00:00.000000Z", "deleted_at": null},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Still holds classes" {
     *   "success": false,
     *   "msg": "Giáo viên còn lớp phụ trách, cần chuyển giao trước khi nghỉ việc.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function resign(ResignTeacherRequest $request, $id, ResignTeacherAction $action)
    {
        try {
            $teacher = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new TeacherResource($teacher), 'Cho giáo viên nghỉ việc thành công.');
    }
}
