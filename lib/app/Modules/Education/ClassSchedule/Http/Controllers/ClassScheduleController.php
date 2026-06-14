<?php

namespace App\Modules\Education\ClassSchedule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\ClassSchedule\Actions\CreateScheduleAction;
use App\Modules\Education\ClassSchedule\Actions\DeleteScheduleAction;
use App\Modules\Education\ClassSchedule\Actions\GetScheduleAction;
use App\Modules\Education\ClassSchedule\Actions\ListScheduleAction;
use App\Modules\Education\ClassSchedule\Actions\UpdateScheduleAction;
use App\Modules\Education\ClassSchedule\Http\Requests\CreateScheduleRequest;
use App\Modules\Education\ClassSchedule\Http\Requests\UpdateScheduleRequest;
use App\Modules\Education\ClassSchedule\Http\Resources\ClassScheduleResource;

/**
 * @group Education - Class Schedule
 *
 * Manage schedules (lịch học) attached to a class.
 *
 * @authenticated
 */
class ClassScheduleController extends Controller
{
    /**
     * List schedules for a class
     *
     * @urlParam classId integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": [
     *     {"id": 1, "class_id": 1, "weekday": 2, "start_time": "19:00:00", "end_time": "20:30:00"},
     *     {"id": 2, "class_id": 1, "weekday": 5, "start_time": "19:00:00", "end_time": "20:30:00"}
     *   ],
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list($classId, ListScheduleAction $action)
    {
        $schedules = $action->handle($classId);

        return $this->respondSuccess(ClassScheduleResource::collection($schedules));
    }

    /**
     * Schedule detail
     *
     * @urlParam id integer required The schedule ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"id": 1, "class_id": 1, "weekday": 2, "start_time": "19:00:00", "end_time": "20:30:00"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetScheduleAction $action)
    {
        return $this->respondSuccess(new ClassScheduleResource($action->handle($id)));
    }

    /**
     * Add schedule to a class
     *
     * @urlParam classId integer required The class ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thêm lịch học thành công.",
     *   "data": {"id": 3, "class_id": 1, "weekday": 3, "start_time": "18:00:00", "end_time": "19:30:00"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateScheduleRequest $request, $classId, CreateScheduleAction $action)
    {
        $schedule = $action->handle($classId, $request->validated());

        return $this->respondSuccess(new ClassScheduleResource($schedule), 'Thêm lịch học thành công.');
    }

    /**
     * Update a schedule
     *
     * @urlParam id integer required The schedule ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật lịch học thành công.",
     *   "data": {"id": 1, "class_id": 1, "weekday": 2, "start_time": "18:30:00", "end_time": "20:00:00"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateScheduleRequest $request, $id, UpdateScheduleAction $action)
    {
        $schedule = $action->handle($id, $request->validated());

        return $this->respondSuccess(new ClassScheduleResource($schedule), 'Cập nhật lịch học thành công.');
    }

    /**
     * Delete a schedule
     *
     * @urlParam id integer required The schedule ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa lịch học thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($id, DeleteScheduleAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa lịch học thành công.');
    }
}
