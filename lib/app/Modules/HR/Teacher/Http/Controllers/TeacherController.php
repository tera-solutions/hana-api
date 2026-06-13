<?php

namespace App\Modules\HR\Teacher\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Teacher\Actions\CreateTeacherAction;
use App\Modules\HR\Teacher\Actions\DeleteTeacherAction;
use App\Modules\HR\Teacher\Actions\GetTeacherAction;
use App\Modules\HR\Teacher\Actions\ListTeacherAction;
use App\Modules\HR\Teacher\Actions\UpdateTeacherAction;
use App\Modules\HR\Teacher\Http\Requests\CreateTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\UpdateTeacherRequest;
use App\Modules\HR\Teacher\Http\Resources\TeacherResource;
use Illuminate\Http\Request;

/**
 * @group HR - Teacher
 *
 * Manage teachers.
 *
 * @authenticated
 */
class TeacherController extends Controller
{
    /**
     * List teachers
     *
     * @queryParam search string Search by teacher code or name. Example: jane
     * @queryParam status string Filter by status. Example: active
     * @queryParam type string Filter by teacher type. Example: fulltime
     * @queryParam business_id integer Filter by business id. Example: 1
     * @queryParam created_from date Created on or after (Y-m-d). Example: 2026-01-01
     * @queryParam created_to date Created on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: code, name, created_at, status. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "code": "TCH001", "name": "Jane Doe", "type": "fulltime", "status": "active", "salary_per_hour": 300000, "user_id": 5, "business_id": 1}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
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
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "teacher": {"id": 1, "code": "TCH001", "name": "Jane Doe", "type": "fulltime", "status": "active", "salary_per_hour": 300000, "business_id": 1},
     *     "statistics": {"classes": 4, "teaching_hours": 120}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetTeacherAction $action)
    {
        $result = $action->handle($id);

        $data = [
            'teacher' => new TeacherResource($result['teacher']),
            'statistics' => $result['statistics'],
        ];

        return $this->respondSuccess($data);
    }

    /**
     * Create teacher
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo Teacher thành công.",
     *   "data": {"id": 1, "code": "TCH001", "name": "Jane Doe", "type": "fulltime", "status": "active", "salary_per_hour": 300000, "business_id": 1},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateTeacherRequest $request, CreateTeacherAction $action)
    {
        $teacher = $action->handle($request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Tạo Teacher thành công.');
    }

    /**
     * Update teacher
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật Teacher thành công.",
     *   "data": {"id": 1, "code": "TCH001", "name": "Jane Doe", "type": "parttime", "status": "active", "salary_per_hour": 350000},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateTeacherRequest $request, $id, UpdateTeacherAction $action)
    {
        $teacher = $action->handle($id, $request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Cập nhật Teacher thành công.');
    }

    /**
     * Delete teacher
     *
     * @urlParam id integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa Teacher thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($id, DeleteTeacherAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa Teacher thành công.');
    }
}
