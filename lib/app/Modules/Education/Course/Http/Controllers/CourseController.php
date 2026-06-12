<?php

namespace App\Modules\Education\Course\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Course\Actions\CreateCourseAction;
use App\Modules\Education\Course\Actions\GetCourseAction;
use App\Modules\Education\Course\Actions\ListCourseAction;
use App\Modules\Education\Course\Actions\RestoreCourseAction;
use App\Modules\Education\Course\Actions\SuspendCourseAction;
use App\Modules\Education\Course\Actions\UpdateCourseAction;
use App\Modules\Education\Course\Http\Requests\CreateCourseRequest;
use App\Modules\Education\Course\Http\Requests\SuspendCourseRequest;
use App\Modules\Education\Course\Http\Requests\UpdateCourseRequest;
use App\Modules\Education\Course\Http\Resources\CourseResource;
use App\Modules\Education\Course\Services\CourseService;
use Illuminate\Http\Request;

/**
 * @group Education - Course
 *
 * Manage courses (training catalogue).
 */
class CourseController extends Controller
{
    public function list(Request $request, ListCourseAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), CourseResource::class);
    }

    public function detail($id, GetCourseAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'course' => new CourseResource($result['course']),
            'statistics' => $result['statistics'],
        ]);
    }

    public function create(CreateCourseRequest $request, CreateCourseAction $action)
    {
        $course = $action->handle($request->validated());

        return $this->respondSuccess(new CourseResource($course), 'Tạo khóa học thành công.');
    }

    public function update(UpdateCourseRequest $request, $id, UpdateCourseAction $action)
    {
        $course = $action->handle($id, $request->validated());

        return $this->respondSuccess(new CourseResource($course), 'Cập nhật khóa học thành công.');
    }

    public function suspend(SuspendCourseRequest $request, $id, SuspendCourseAction $action)
    {
        try {
            $course = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new CourseResource($course), 'Ngừng khóa học thành công.');
    }

    public function restore($id, RestoreCourseAction $action)
    {
        try {
            $course = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new CourseResource($course), 'Khôi phục khóa học thành công.');
    }

    public function statistics($id, CourseService $service)
    {
        return $this->respondSuccess($service->operationalStatistics($id));
    }

    public function financialSummary($id, CourseService $service)
    {
        return $this->respondSuccess($service->financialSummary($id));
    }

    public function ratingSummary($id, CourseService $service)
    {
        return $this->respondSuccess($service->ratingSummary($id));
    }
}
