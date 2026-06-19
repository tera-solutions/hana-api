<?php

namespace App\Modules\Education\ExamVersion\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Exam\Http\Resources\ExamResource;
use App\Modules\Education\ExamVersion\Actions\GetExamVersionAction;
use App\Modules\Education\ExamVersion\Actions\ListExamVersionAction;

/**
 * @group Education - Exam Versions
 *
 * Manage an exam's version lineage (exam.md §IV "Version đề thi").
 *
 * @authenticated
 */
class ExamVersionController extends Controller
{
    /**
     * List versions of an exam
     *
     * Lists every version in the exam's lineage, oldest first.
     *
     * @urlParam examId integer required Any exam ID in the lineage. Example: 1
     */
    public function list($examId, ListExamVersionAction $action)
    {
        return $this->respondSuccess(ExamResource::collection($action->handle($examId)));
    }

    /**
     * Version detail
     *
     * @urlParam id integer required The exam version ID. Example: 1
     */
    public function detail($id, GetExamVersionAction $action)
    {
        return $this->respondSuccess(new ExamResource($action->handle($id)));
    }
}
