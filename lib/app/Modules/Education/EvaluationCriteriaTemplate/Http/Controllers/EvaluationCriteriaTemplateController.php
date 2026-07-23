<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\EvaluationCriteriaTemplate\Actions\CreateEvaluationCriteriaTemplateAction;
use App\Modules\Education\EvaluationCriteriaTemplate\Actions\GetEvaluationCriteriaTemplateAction;
use App\Modules\Education\EvaluationCriteriaTemplate\Actions\ListEvaluationCriteriaTemplateAction;
use App\Modules\Education\EvaluationCriteriaTemplate\Actions\RestoreEvaluationCriteriaTemplateAction;
use App\Modules\Education\EvaluationCriteriaTemplate\Actions\SuspendEvaluationCriteriaTemplateAction;
use App\Modules\Education\EvaluationCriteriaTemplate\Actions\UpdateEvaluationCriteriaTemplateAction;
use App\Modules\Education\EvaluationCriteriaTemplate\Http\Requests\CreateEvaluationCriteriaTemplateRequest;
use App\Modules\Education\EvaluationCriteriaTemplate\Http\Requests\UpdateEvaluationCriteriaTemplateRequest;
use App\Modules\Education\EvaluationCriteriaTemplate\Http\Resources\EvaluationCriteriaTemplateResource;
use Illuminate\Http\Request;

/**
 * @group Education - Evaluation Criteria Template
 *
 * Reusable rubrics (named criteria lists) to pre-fill when creating an
 * Evaluation. Shared (business-wide) templates are admin-authored; a teacher
 * may also define their own private ones.
 *
 * @authenticated
 */
class EvaluationCriteriaTemplateController extends Controller
{
    /**
     * List criteria templates
     *
     * Returns templates shared business-wide plus any the caller authored themselves.
     *
     * @queryParam evaluation_type string Filter: teacher|student|parent. Example: teacher
     * @queryParam search string Search by name. Example: chuẩn
     * @queryParam status string Filter: active|inactive. Example: active
     */
    public function list(Request $request, ListEvaluationCriteriaTemplateAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), EvaluationCriteriaTemplateResource::class);
    }

    /**
     * Criteria template detail
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function detail($id, GetEvaluationCriteriaTemplateAction $action)
    {
        return $this->respondSuccess(new EvaluationCriteriaTemplateResource($action->handle($id)));
    }

    /**
     * Create criteria template
     *
     * `is_shared` is silently dropped to false for a non-admin caller.
     */
    public function create(CreateEvaluationCriteriaTemplateRequest $request, CreateEvaluationCriteriaTemplateAction $action)
    {
        return $this->respondSuccess(
            new EvaluationCriteriaTemplateResource($action->handle($request->validated())),
            'Tạo bảng tiêu chí thành công.',
        );
    }

    /**
     * Update criteria template
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function update($id, UpdateEvaluationCriteriaTemplateRequest $request, UpdateEvaluationCriteriaTemplateAction $action)
    {
        try {
            $template = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EvaluationCriteriaTemplateResource($template), 'Cập nhật bảng tiêu chí thành công.');
    }

    /**
     * Suspend criteria template
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function suspend($id, SuspendEvaluationCriteriaTemplateAction $action)
    {
        try {
            $template = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EvaluationCriteriaTemplateResource($template), 'Ngừng sử dụng bảng tiêu chí thành công.');
    }

    /**
     * Restore criteria template
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function restore($id, RestoreEvaluationCriteriaTemplateAction $action)
    {
        try {
            $template = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new EvaluationCriteriaTemplateResource($template), 'Khôi phục bảng tiêu chí thành công.');
    }
}
