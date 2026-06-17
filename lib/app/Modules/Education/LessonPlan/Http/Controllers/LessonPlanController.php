<?php

namespace App\Modules\Education\LessonPlan\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\LessonPlan\Actions\ArchiveLessonPlanAction;
use App\Modules\Education\LessonPlan\Actions\CloneLessonPlanAction;
use App\Modules\Education\LessonPlan\Actions\CreateLessonPlanAction;
use App\Modules\Education\LessonPlan\Actions\GetLessonPlanAction;
use App\Modules\Education\LessonPlan\Actions\ListLessonPlanAction;
use App\Modules\Education\LessonPlan\Actions\PublishLessonPlanAction;
use App\Modules\Education\LessonPlan\Actions\RestoreLessonPlanAction;
use App\Modules\Education\LessonPlan\Actions\UpdateLessonPlanAction;
use App\Modules\Education\LessonPlan\Http\Requests\CloneLessonPlanRequest;
use App\Modules\Education\LessonPlan\Http\Requests\CreateLessonPlanRequest;
use App\Modules\Education\LessonPlan\Http\Requests\PublishLessonPlanRequest;
use App\Modules\Education\LessonPlan\Http\Requests\UpdateLessonPlanRequest;
use App\Modules\Education\LessonPlan\Http\Resources\LessonPlanResource;
use Illuminate\Http\Request;

/**
 * @group Education - Lesson Plan
 *
 * Manage teaching lesson plans (templates) per course and level.
 *
 * @authenticated
 */
class LessonPlanController extends Controller
{
    /**
     * List lesson plans
     *
     * @queryParam search string Search by plan code or name. Example: Kids
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam level_id integer Filter by level. Example: 1
     * @queryParam status string Filter by status: draft, reviewing, published, archived. Example: published
     * @queryParam sort_by string Sort column: plan_code, plan_name, version, total_lessons, status, created_at. Example: created_at
     * @queryParam sort_dir string Sort direction: asc or desc. Example: desc
     * @queryParam per_page integer Page size (default 20). Example: 20
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "items": [{"id": 1, "plan_code": "KIDS_STARTER_V1", "plan_name": "Kids Starter", "course_id": 1, "version": 1, "total_lessons": 4, "status": "draft"}],
     *     "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListLessonPlanAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), LessonPlanResource::class);
    }

    /**
     * Lesson plan detail
     *
     * Returns the plan with its lessons (and materials), version history and usage.
     *
     * @urlParam id integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "plan": {"id": 1, "plan_code": "KIDS_STARTER_V1", "plan_name": "Kids Starter", "version": 1, "total_lessons": 1, "status": "draft", "lessons": [{"id": 1, "lesson_no": 1, "lesson_title": "Alphabet"}], "versions": []},
     *     "usage": {"classes": 0}
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detail($id, GetLessonPlanAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'plan' => new LessonPlanResource($result['plan']),
            'usage' => $result['usage'],
        ]);
    }

    /**
     * Create lesson plan
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Tạo giáo án thành công.",
     *   "data": {"id": 1, "plan_code": "KIDS_STARTER_V1", "plan_name": "Kids Starter", "version": 1, "status": "draft", "total_lessons": 0},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateLessonPlanRequest $request, CreateLessonPlanAction $action)
    {
        $plan = $action->handle($request->validated());

        return $this->respondSuccess(new LessonPlanResource($plan), 'Tạo giáo án thành công.');
    }

    /**
     * Update lesson plan
     *
     * Blocked once published or used by a class (must clone instead).
     *
     * @urlParam id integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật giáo án thành công.",
     *   "data": {"id": 1, "plan_name": "Kids Starter Updated", "status": "draft"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateLessonPlanRequest $request, $id, UpdateLessonPlanAction $action)
    {
        try {
            $plan = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanResource($plan), 'Cập nhật giáo án thành công.');
    }

    /**
     * Clone lesson plan
     *
     * Creates a new draft plan (next version) copying lessons and materials.
     *
     * @urlParam id integer required The source lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Sao chép giáo án thành công.",
     *   "data": {"id": 2, "plan_code": "KIDS_STARTER_V2", "version": 2, "status": "draft"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function clone(CloneLessonPlanRequest $request, $id, CloneLessonPlanAction $action)
    {
        $plan = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LessonPlanResource($plan), 'Sao chép giáo án thành công.');
    }

    /**
     * Publish lesson plan
     *
     * Requires at least one valid lesson; records a version entry.
     *
     * @urlParam id integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xuất bản giáo án thành công.",
     *   "data": {"id": 1, "status": "published", "published_at": "2026-06-16T00:00:00.000000Z"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function publish(PublishLessonPlanRequest $request, $id, PublishLessonPlanAction $action)
    {
        try {
            $plan = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanResource($plan), 'Xuất bản giáo án thành công.');
    }

    /**
     * Archive lesson plan
     *
     * @urlParam id integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Ngừng sử dụng giáo án thành công.",
     *   "data": {"id": 1, "status": "archived"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function archive($id, ArchiveLessonPlanAction $action)
    {
        try {
            $plan = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanResource($plan), 'Ngừng sử dụng giáo án thành công.');
    }

    /**
     * Restore lesson plan
     *
     * Brings an archived plan back to draft so it can be edited and re-published.
     *
     * @urlParam id integer required The lesson plan ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Khôi phục giáo án thành công.",
     *   "data": {"id": 1, "status": "draft"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function restore($id, RestoreLessonPlanAction $action)
    {
        try {
            $plan = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new LessonPlanResource($plan), 'Khôi phục giáo án thành công.');
    }
}
