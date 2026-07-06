<?php

namespace App\Modules\Education\ClassRoom\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\ClassRoom\Actions\CreateClassAction;
use App\Modules\Education\ClassRoom\Actions\GetClassAction;
use App\Modules\Education\ClassRoom\Actions\ListClassAction;
use App\Modules\Education\ClassRoom\Actions\RestoreClassAction;
use App\Modules\Education\ClassRoom\Actions\SummaryClassAction;
use App\Modules\Education\ClassRoom\Actions\SuspendClassAction;
use App\Modules\Education\ClassRoom\Actions\UpdateClassAction;
use App\Modules\Education\ClassRoom\Http\Requests\CreateClassRequest;
use App\Modules\Education\ClassRoom\Http\Requests\SuspendClassRequest;
use App\Modules\Education\ClassRoom\Http\Requests\UpdateClassRequest;
use App\Modules\Education\ClassRoom\Http\Resources\ClassResource;
use Illuminate\Http\Request;

/**
 * @group Education - Class
 *
 * Manage classes (lớp học) — the core operational unit linking courses, students,
 * teachers, rooms, curriculum and attendance.
 *
 * @authenticated
 */
class ClassController extends Controller
{
    /**
     * List classes
     *
     * @queryParam search string Search by class name or code. Example: IELTS
     * @queryParam course_id integer Filter by course ID. Example: 1
     * @queryParam lesson_plan_id integer Filter by lesson plan ID. Example: 1
     * @queryParam teacher_id integer Filter by teacher ID. Example: 2
     * @queryParam assignee_id integer Filter by assignee (staff) user ID. Example: 5
     * @queryParam weekday integer Filter by schedule weekday (1=Mon … 7=Sun). Example: 2
     * @queryParam shift string Filter by shift (schedule start time): morning, afternoon, evening. Example: evening
     * @queryParam status string Filter by status: draft, upcoming, active, suspended, completed. Example: active
     * @queryParam start_from date Classes starting on or after (Y-m-d). Example: 2026-07-01
     * @queryParam start_to date Classes starting on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: code, name, start_date, status, created_at. Example: start_date
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: asc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {
     *         "id": 1, "code": "IELTS-F-2026-07", "name": "IELTS Foundation - Khai giảng tháng 7",
     *         "course_id": 1, "course": {"id": 1, "code": "IELTS_F", "name": "IELTS Foundation"},
     *         "teacher_id": 2, "teacher": {"id": 2, "full_name": "Nguyễn Văn A", "avatar": null},
     *         "assignee_id": 5, "assignee": {"id": 5, "name": "Trần Thị B"},
     *         "room_id": null, "learning_type": "scheduled",
     *         "start_date": "2026-07-01", "end_date": "2026-09-30",
     *         "status": "upcoming",
     *         "min_warning_capacity": 5, "min_capacity": 8,
     *         "max_warning_capacity": 18, "max_capacity": 20,
     *         "capacity_warning": null,
     *         "use_course_curriculum": true, "description": null,
     *         "schedules": [
     *           {"id": 1, "class_id": 1, "weekday": 2, "start_time": "19:00:00", "end_time": "20:30:00"},
     *           {"id": 2, "class_id": 1, "weekday": 5, "start_time": "19:00:00", "end_time": "20:30:00"}
     *         ],
     *         "business_id": 1,
     *         "created_by": 1, "updated_by": null, "deleted_by": null,
     *         "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T08:00:00.000000Z", "deleted_at": null
     *       }
     *     ],
     *     "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListClassAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), ClassResource::class);
    }

    /**
     * Class summary
     *
     * Aggregate counters for the (teacher-scoped) class list. Honours the same
     * filters as the list endpoint.
     *
     * @queryParam search string Search by class name or code. Example: IELTS
     * @queryParam course_id integer Filter by course ID. Example: 1
     * @queryParam lesson_plan_id integer Filter by lesson plan ID. Example: 1
     * @queryParam teacher_id integer Filter by teacher ID. Example: 2
     * @queryParam status string Filter by status: draft, upcoming, active, suspended, completed. Example: active
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "total": 12,
     *     "by_status": {"draft": 1, "upcoming": 2, "active": 8, "suspended": 0, "completed": 1},
     *     "total_students": 184
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function summary(Request $request, SummaryClassAction $action)
    {
        return $this->respondSuccess($action->handle($request->all()));
    }

    /**
     * Class detail
     *
     * Returns full class information plus student, operational and financial statistics.
     *
     * @urlParam id integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "class": {
     *       "id": 1, "code": "IELTS-F-2026-07", "name": "IELTS Foundation - Khai giảng tháng 7",
     *       "course_id": 1, "course": {"id": 1, "code": "IELTS_F", "name": "IELTS Foundation"},
     *       "teacher_id": 2, "teacher": {"id": 2, "full_name": "Nguyễn Văn A", "avatar": null},
     *       "assignee_id": 5, "assignee": {"id": 5, "name": "Trần Thị B"},
     *       "learning_type": "scheduled", "status": "upcoming",
     *       "start_date": "2026-07-01", "end_date": "2026-09-30",
     *       "min_warning_capacity": 5, "min_capacity": 8, "max_warning_capacity": 18, "max_capacity": 20,
     *       "schedules": [
     *         {"id": 1, "class_id": 1, "weekday": 2, "start_time": "19:00:00", "end_time": "20:30:00"}
     *       ],
     *       "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T08:00:00.000000Z", "deleted_at": null
     *     },
     *     "statistics": {
     *       "students": {"total": 12, "active": 10, "reserved": 1, "completed": 1, "dropped": 0},
     *       "operational": {"total_sessions": 24, "completed_sessions": 0, "pending_sessions": 24, "completion_rate": 0, "avg_attendance_rate": 0},
     *       "financial": {"total_revenue": 0, "recognized_revenue": 0, "debt": 0, "refunds": 0}
     *     }
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetClassAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'class' => new ClassResource($result['class']),
            'statistics' => $result['statistics'],
        ]);
    }

    /**
     * Create class
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo lớp học thành công.",
     *   "data": {
     *     "id": 1, "code": "IELTS-F-2026-07", "name": "IELTS Foundation - Khai giảng tháng 7",
     *     "course_id": 1, "teacher_id": 2, "learning_type": "scheduled",
     *     "start_date": "2026-07-01", "end_date": "2026-09-30",
     *     "status": "upcoming", "max_capacity": 20,
     *     "schedules": [
     *       {"id": 1, "class_id": 1, "weekday": 2, "start_time": "19:00:00", "end_time": "20:30:00"}
     *     ],
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T08:00:00.000000Z", "deleted_at": null
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateClassRequest $request, CreateClassAction $action)
    {
        $class = $action->handle($request->validated());

        return $this->respondSuccess(new ClassResource($class), 'Tạo lớp học thành công.');
    }

    /**
     * Update class
     *
     * Partial update — only send fields to change. Code and course_id are immutable
     * once sessions exist.
     *
     * @urlParam id integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật lớp học thành công.",
     *   "data": {
     *     "id": 1, "code": "IELTS-F-2026-07", "name": "IELTS Foundation (cập nhật)",
     *     "status": "upcoming", "updated_at": "2026-06-13T09:00:00.000000Z"
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateClassRequest $request, $id, UpdateClassAction $action)
    {
        $class = $action->handle($id, $request->validated());

        return $this->respondSuccess(new ClassResource($class), 'Cập nhật lớp học thành công.');
    }

    /**
     * Suspend class
     *
     * Moves the class to "suspended" status and records the reason.
     *
     * @urlParam id integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạm ngừng lớp học thành công.",
     *   "data": {"id": 1, "code": "IELTS-F-2026-07", "status": "suspended"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Already suspended" {
     *   "success": false,
     *   "msg": "Lớp học đang ở trạng thái tạm ngừng.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendClassRequest $request, $id, SuspendClassAction $action)
    {
        try {
            $class = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ClassResource($class), 'Tạm ngừng lớp học thành công.');
    }

    /**
     * Restore class
     *
     * Reactivates a suspended class back to upcoming or active based on start date.
     *
     * @urlParam id integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục lớp học thành công.",
     *   "data": {"id": 1, "code": "IELTS-F-2026-07", "status": "upcoming"},
     *   "code": 200,
     *   "errors": null
     * }
     * @response 200 scenario="Not suspended" {
     *   "success": false,
     *   "msg": "Chỉ có thể khôi phục lớp học đang tạm ngừng.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function restore($id, RestoreClassAction $action)
    {
        try {
            $class = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new ClassResource($class), 'Khôi phục lớp học thành công.');
    }
}
