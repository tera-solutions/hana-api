<?php

namespace App\Modules\Education\StudentLevel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\StudentLevel\Actions\AdjustStudentLevelAction;
use App\Modules\Education\StudentLevel\Actions\GetStudentLevelAction;
use App\Modules\Education\StudentLevel\Actions\HistoryStudentLevelAction;
use App\Modules\Education\StudentLevel\Actions\PlacementStudentLevelAction;
use App\Modules\Education\StudentLevel\Actions\PromoteStudentLevelAction;
use App\Modules\Education\StudentLevel\Http\Requests\AdjustRequest;
use App\Modules\Education\StudentLevel\Http\Requests\PlacementRequest;
use App\Modules\Education\StudentLevel\Http\Requests\PromoteRequest;
use App\Modules\Education\StudentLevel\Http\Resources\StudentLevelHistoryResource;
use App\Modules\Education\StudentLevel\Http\Resources\StudentLevelResource;

/**
 * @group Education - Student Level
 *
 * A student's current proficiency level: placement, promotion, manual adjustment
 * and change history (student-level.md).
 *
 * @authenticated
 */
class StudentLevelController extends Controller
{
    /**
     * Student level detail
     *
     * Current level with progress indicators and change history.
     *
     * @urlParam studentId integer required The student ID. Example: 1
     */
    public function detail($studentId, GetStudentLevelAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($studentId),
            'Thao tác thành công',
            fn ($result) => [
                'student_level' => new StudentLevelResource($result['student_level']),
                'progress' => $result['progress'],
                'histories' => StudentLevelHistoryResource::collection($result['histories']),
            ],
        );
    }

    /**
     * Placement
     *
     * Records a placement assessment and assigns the resulting level (BR001/BR002).
     */
    public function placement(PlacementRequest $request, PlacementStudentLevelAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($request->validated()),
            'Đánh giá đầu vào và gán cấp độ thành công.',
            fn ($studentLevel) => new StudentLevelResource($studentLevel),
        );
    }

    /**
     * Promote
     *
     * Promotes the student to the next level (or an explicit target).
     *
     * @urlParam id integer required The student-level ID. Example: 1
     */
    public function promote(PromoteRequest $request, $id, PromoteStudentLevelAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Xét lên cấp độ thành công.',
            fn ($studentLevel) => new StudentLevelResource($studentLevel),
        );
    }

    /**
     * Adjust
     *
     * Manually moves the student to another level in the same course (BR002).
     *
     * @urlParam id integer required The student-level ID. Example: 1
     */
    public function adjust(AdjustRequest $request, $id, AdjustStudentLevelAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Điều chỉnh cấp độ thành công.',
            fn ($studentLevel) => new StudentLevelResource($studentLevel),
        );
    }

    /**
     * Level history
     *
     * @urlParam id integer required The student-level ID. Example: 1
     */
    public function history($id, HistoryStudentLevelAction $action)
    {
        return $this->respondSuccess(StudentLevelHistoryResource::collection($action->handle($id)));
    }
}
