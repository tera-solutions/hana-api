<?php

namespace App\Modules\Education\Evaluation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Evaluation\Actions\CreateEvaluationAction;
use App\Modules\Education\Evaluation\Actions\DeleteEvaluationAction;
use App\Modules\Education\Evaluation\Actions\GetEvaluationAction;
use App\Modules\Education\Evaluation\Actions\ListEvaluationAction;
use App\Modules\Education\Evaluation\Actions\TransitionEvaluationAction;
use App\Modules\Education\Evaluation\Actions\UpdateEvaluationAction;
use App\Modules\Education\Evaluation\Http\Requests\CreateEvaluationRequest;
use App\Modules\Education\Evaluation\Http\Requests\UpdateEvaluationRequest;
use App\Modules\Education\Evaluation\Http\Resources\EvaluationResource;
use Illuminate\Http\Request;

/**
 * @group Education - Evaluation
 *
 * Teacher / student / parent evaluations: collect per-criterion ratings, auto-compute
 * the total score and classification, and run the draft → submitted → approved →
 * locked workflow (evaluation.md).
 *
 * @authenticated
 */
class EvaluationController extends Controller
{
    /**
     * List evaluations
     *
     * @queryParam search string Search by code or comment. Example: EVAL
     * @queryParam evaluation_type string Filter: teacher|student|parent. Example: student
     * @queryParam evaluator_type string Filter: parent|student|manager|teacher|cskh. Example: teacher
     * @queryParam target_id integer Filter by evaluated target id. Example: 1
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam class_room_id integer Filter by class. Example: 1
     * @queryParam lesson_id integer Filter by lesson. Example: 1
     * @queryParam evaluation_period string Filter by period. Example: final
     * @queryParam classification string Filter by classification. Example: excellent
     * @queryParam status string Filter by status. Example: approved
     * @queryParam score_from number Minimum total score. Example: 3
     * @queryParam score_to number Maximum total score. Example: 5
     * @queryParam evaluated_from date Evaluated on/after (Y-m-d). Example: 2026-06-01
     * @queryParam evaluated_to date Evaluated on/before (Y-m-d). Example: 2026-06-30
     * @queryParam sort_by string Sort column. Example: created_at
     * @queryParam sort_dir string asc|desc (default desc). Example: desc
     * @queryParam per_page integer Page size: 20, 50 or 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"items": [], "pagination": {"total": 0, "per_page": 20, "current_page": 1, "last_page": 1}}, "code": 200, "errors": null}
     */
    public function list(Request $request, ListEvaluationAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), EvaluationResource::class);
    }

    /**
     * Evaluation detail
     *
     * @urlParam id integer required The evaluation ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Thao tác thành công", "data": {"id": 1, "evaluation_code": "EVAL000001"}, "code": 200, "errors": null}
     */
    public function detail($id, GetEvaluationAction $action)
    {
        return $this->respondSuccess(new EvaluationResource($action->handle($id)));
    }

    /**
     * Create evaluation
     *
     * The total score and classification are computed from the per-criterion ratings (BR-03).
     *
     * @response 200 {"success": true, "msg": "Tạo đánh giá thành công.", "data": {"id": 1, "status": "draft", "score": "4.50"}, "code": 200, "errors": null}
     * @response 200 scenario="Duplicate" {"success": false, "msg": "Đã tồn tại đánh giá cho đối tượng này trong cùng kỳ.", "data": null, "code": 200, "errors": null}
     */
    public function create(CreateEvaluationRequest $request, CreateEvaluationAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($request->validated()),
            'Tạo đánh giá thành công.',
            fn ($evaluation) => new EvaluationResource($evaluation),
        );
    }

    /**
     * Update evaluation
     *
     * Locked evaluations cannot be edited (BR-02).
     *
     * @urlParam id integer required The evaluation ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Cập nhật đánh giá thành công.", "data": {"id": 1}, "code": 200, "errors": null}
     */
    public function update(UpdateEvaluationRequest $request, $id, UpdateEvaluationAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Cập nhật đánh giá thành công.',
            fn ($evaluation) => new EvaluationResource($evaluation),
        );
    }

    /**
     * Delete evaluation
     *
     * @urlParam id integer required The evaluation ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Xóa đánh giá thành công.", "data": null, "code": 200, "errors": null}
     */
    public function delete($id, DeleteEvaluationAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Xóa đánh giá thành công.',
            fn () => null,
        );
    }

    /**
     * Submit evaluation
     *
     * @urlParam id integer required The evaluation ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Gửi đánh giá thành công.", "data": {"id": 1, "status": "submitted"}, "code": 200, "errors": null}
     */
    public function submit($id, TransitionEvaluationAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('submit', $id),
            'Gửi đánh giá thành công.',
            fn ($evaluation) => new EvaluationResource($evaluation),
        );
    }

    /**
     * Approve evaluation
     *
     * @urlParam id integer required The evaluation ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Duyệt đánh giá thành công.", "data": {"id": 1, "status": "approved"}, "code": 200, "errors": null}
     */
    public function approve($id, TransitionEvaluationAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('approve', $id),
            'Duyệt đánh giá thành công.',
            fn ($evaluation) => new EvaluationResource($evaluation),
        );
    }

    /**
     * Reject evaluation
     *
     * @urlParam id integer required The evaluation ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Từ chối đánh giá thành công.", "data": {"id": 1, "status": "rejected"}, "code": 200, "errors": null}
     */
    public function reject($id, TransitionEvaluationAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('reject', $id),
            'Từ chối đánh giá thành công.',
            fn ($evaluation) => new EvaluationResource($evaluation),
        );
    }

    /**
     * Lock evaluation
     *
     * Locks the evaluation period; the record can no longer be edited or deleted (BR-02).
     *
     * @urlParam id integer required The evaluation ID. Example: 1
     *
     * @response 200 {"success": true, "msg": "Khóa đánh giá thành công.", "data": {"id": 1, "status": "locked"}, "code": 200, "errors": null}
     */
    public function lock($id, TransitionEvaluationAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle('lock', $id),
            'Khóa đánh giá thành công.',
            fn ($evaluation) => new EvaluationResource($evaluation),
        );
    }
}
