<?php

namespace App\Modules\Education\CourseCurriculum\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\CourseCurriculum\Actions\CreateCourseCurriculumAction;
use App\Modules\Education\CourseCurriculum\Actions\DeleteCourseCurriculumAction;
use App\Modules\Education\CourseCurriculum\Actions\GetCourseCurriculumAction;
use App\Modules\Education\CourseCurriculum\Actions\ListCourseCurriculumAction;
use App\Modules\Education\CourseCurriculum\Actions\UpdateCourseCurriculumAction;
use App\Modules\Education\CourseCurriculum\Http\Requests\CreateCourseCurriculumRequest;
use App\Modules\Education\CourseCurriculum\Http\Requests\UpdateCourseCurriculumRequest;
use App\Modules\Education\CourseCurriculum\Http\Resources\CourseCurriculumResource;
use Illuminate\Http\Request;

/**
 * @group Education - Course Curriculum
 *
 * Manage a course's curriculum template (chương trình học) — cloned into a
 * class's own curriculum (edu_class_curriculums) when the class opts into
 * use_course_curriculum on create.
 *
 * @authenticated
 */
class CourseCurriculumController extends Controller
{
    /**
     * List course curriculum items
     *
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam search string Search by title. Example: Listening
     * @queryParam sort_by string Sort column: order, title, created_at (default order). Example: order
     * @queryParam sort_dir string Sort direction: asc or desc (default asc). Example: asc
     * @queryParam per_page integer Page size (default 20). Example: 20
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {"items": [{"id": 1, "course_id": 1, "title": "Nghe hiểu — Listening comprehension", "order": 1, "content": null}], "pagination": {"total": 1, "per_page": 20, "current_page": 1, "last_page": 1}},
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list(Request $request, ListCourseCurriculumAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), CourseCurriculumResource::class);
    }

    /**
     * Course curriculum item detail
     *
     * @urlParam id integer required The curriculum item ID. Example: 1
     */
    public function detail($id, GetCourseCurriculumAction $action)
    {
        return $this->respondSuccess(new CourseCurriculumResource($action->handle($id)));
    }

    /**
     * Create course curriculum item
     */
    public function create(CreateCourseCurriculumRequest $request, CreateCourseCurriculumAction $action)
    {
        $curriculum = $action->handle($request->validated());

        return $this->respondSuccess(new CourseCurriculumResource($curriculum), 'Tạo nội dung chương trình học thành công.');
    }

    /**
     * Update course curriculum item
     *
     * @urlParam id integer required The curriculum item ID. Example: 1
     */
    public function update(UpdateCourseCurriculumRequest $request, $id, UpdateCourseCurriculumAction $action)
    {
        $curriculum = $action->handle($id, $request->validated());

        return $this->respondSuccess(new CourseCurriculumResource($curriculum), 'Cập nhật nội dung chương trình học thành công.');
    }

    /**
     * Delete course curriculum item
     *
     * @urlParam id integer required The curriculum item ID. Example: 1
     */
    public function delete($id, DeleteCourseCurriculumAction $action)
    {
        $action->handle($id);

        return $this->respondSuccess(null, 'Xóa nội dung chương trình học thành công.');
    }
}
