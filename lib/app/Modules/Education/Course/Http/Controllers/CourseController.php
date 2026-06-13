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
 *
 * @authenticated
 */
class CourseController extends Controller
{
    /**
     * List courses
     *
     * Paginated list of courses for the current business.
     *
     * @queryParam search string Search by course name or code. Example: IELTS
     * @queryParam status string Filter by status: active or inactive. Example: active
     * @queryParam is_active boolean Filter by active flag (alternative to status). Example: true
     * @queryParam duration_min integer Minimum duration in minutes. Example: 60
     * @queryParam duration_max integer Maximum duration in minutes. Example: 120
     * @queryParam price_min number Minimum price per lesson. Example: 100000
     * @queryParam price_max number Maximum price per lesson. Example: 500000
     * @queryParam created_by integer Filter by creator user id. Example: 1
     * @queryParam created_from date Created on or after (Y-m-d). Example: 2026-01-01
     * @queryParam created_to date Created on or before (Y-m-d). Example: 2026-12-31
     * @queryParam sort_by string Sort column: code, name, duration_minutes, price_per_lesson, created_at. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100 (default 20). Example: 20
     * @queryParam page integer Page number (default 1). Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [
     *       {"id": 1, "code": "CRS001", "name": "IELTS Foundation", "thumbnail": null, "duration_minutes": 90, "price_per_lesson": 200000, "description": "Beginner IELTS course", "is_active": true, "business_id": 1, "created_at": "2026-01-01T00:00:00.000000Z", "updated_at": "2026-01-01T00:00:00.000000Z", "deleted_at": null}
     *     ],
     *     "pagination": {"total": 1, "per_page": 15, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListCourseAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), CourseResource::class);
    }

    /**
     * Course detail
     *
     * @urlParam id integer required The course ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "course": {"id": 1, "code": "CRS001", "name": "IELTS Foundation", "duration_minutes": 90, "price_per_lesson": 200000, "is_active": true, "business_id": 1},
     *     "statistics": {"students": 12, "classes": 3}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetCourseAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'course' => new CourseResource($result['course']),
            'statistics' => $result['statistics'],
        ]);
    }

    /**
     * Create course
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo khóa học thành công.",
     *   "data": {"id": 1, "code": "CRS001", "name": "IELTS Foundation", "duration_minutes": 90, "price_per_lesson": 200000, "is_active": true, "business_id": 1},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateCourseRequest $request, CreateCourseAction $action)
    {
        $course = $action->handle($request->validated());

        return $this->respondSuccess(new CourseResource($course), 'Tạo khóa học thành công.');
    }

    /**
     * Update course
     *
     * @urlParam id integer required The course ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật khóa học thành công.",
     *   "data": {"id": 1, "code": "CRS001", "name": "IELTS Foundation", "is_active": true, "business_id": 1},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateCourseRequest $request, $id, UpdateCourseAction $action)
    {
        $course = $action->handle($id, $request->validated());

        return $this->respondSuccess(new CourseResource($course), 'Cập nhật khóa học thành công.');
    }

    /**
     * Suspend course
     *
     * @urlParam id integer required The course ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Ngừng khóa học thành công.",
     *   "data": {"id": 1, "code": "CRS001", "name": "IELTS Foundation", "is_active": false},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function suspend(SuspendCourseRequest $request, $id, SuspendCourseAction $action)
    {
        try {
            $course = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new CourseResource($course), 'Ngừng khóa học thành công.');
    }

    /**
     * Restore course
     *
     * @urlParam id integer required The course ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục khóa học thành công.",
     *   "data": {"id": 1, "code": "CRS001", "name": "IELTS Foundation", "is_active": true},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function restore($id, RestoreCourseAction $action)
    {
        try {
            $course = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new CourseResource($course), 'Khôi phục khóa học thành công.');
    }

    /**
     * Course operational statistics
     *
     * @urlParam id integer required The course ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"students": 12, "classes": 3, "lessons": 48, "completion_rate": 0.85},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function statistics($id, CourseService $service)
    {
        return $this->respondSuccess($service->operationalStatistics($id));
    }

    /**
     * Course financial summary
     *
     * @urlParam id integer required The course ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"revenue": 24000000, "paid": 18000000, "outstanding": 6000000},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function financialSummary($id, CourseService $service)
    {
        return $this->respondSuccess($service->financialSummary($id));
    }

    /**
     * Course rating summary
     *
     * @urlParam id integer required The course ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"average": 4.6, "count": 25},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function ratingSummary($id, CourseService $service)
    {
        return $this->respondSuccess($service->ratingSummary($id));
    }
}
