<?php

namespace App\Modules\Education\LessonPlanMaterial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\LessonPlanMaterial\Actions\AttachMaterialAction;
use App\Modules\Education\LessonPlanMaterial\Actions\DetachMaterialAction;
use App\Modules\Education\LessonPlanMaterial\Http\Requests\AttachMaterialRequest;
use App\Modules\Education\LessonPlanMaterial\Http\Resources\LessonPlanMaterialResource;

/**
 * @group Education - Lesson Plan Materials
 *
 * Attach and detach learning materials on a lesson template (lesson-plan.md §14).
 *
 * @authenticated
 */
class LessonPlanMaterialController extends Controller
{
    /**
     * Attach a material to a lesson
     *
     * @urlParam id integer required The lesson ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Gắn tài liệu thành công.",
     *   "data": {"id": 1, "lesson_plan_lesson_id": 1, "file_id": 10, "material_type": "pdf"},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function attach(AttachMaterialRequest $request, $id, AttachMaterialAction $action)
    {
        $material = $action->handle($id, $request->validated());

        return $this->respondSuccess(new LessonPlanMaterialResource($material), 'Gắn tài liệu thành công.');
    }

    /**
     * Detach a material
     *
     * @urlParam id integer required The material ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Gỡ tài liệu thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function detach($id, DetachMaterialAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Gỡ tài liệu thành công.');
    }
}
