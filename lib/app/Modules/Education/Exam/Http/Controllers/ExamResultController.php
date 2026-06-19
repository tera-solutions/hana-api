<?php

namespace App\Modules\Education\Exam\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Exam\Actions\GradeExamResultAction;
use App\Modules\Education\Exam\Actions\PromoteExamResultAction;
use App\Modules\Education\Exam\Actions\PublishExamResultAction;
use App\Modules\Education\Exam\Http\Requests\GradeExamResultRequest;
use App\Modules\Education\Exam\Http\Requests\PromoteExamResultRequest;
use App\Modules\Education\Exam\Http\Resources\ExamResultResource;
use App\Modules\Education\StudentLevel\Http\Resources\StudentLevelResource;

/**
 * @group Education - Exam Result
 *
 * Grade sittings, publish results and promote on a passing result (exam.md §XI–§XIII).
 * The {id} path parameter is the exam registration ID (a candidate's slot in a sitting).
 *
 * @authenticated
 */
class ExamResultController extends Controller
{
    /**
     * Grade a result
     *
     * Records per-skill scores for a registered candidate; the total is capped at the
     * exam's maximum (BR006).
     *
     * @urlParam id integer required The exam registration ID. Example: 1
     */
    public function grade(GradeExamResultRequest $request, $id, GradeExamResultAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Chấm thi thành công.',
            fn ($result) => new ExamResultResource($result),
        );
    }

    /**
     * Publish a result
     *
     * @urlParam id integer required The exam registration ID. Example: 1
     */
    public function publish($id, PublishExamResultAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Công bố kết quả thành công.',
            fn ($result) => new ExamResultResource($result),
        );
    }

    /**
     * Promote on result
     *
     * Promotes the candidate to the next level on a passing result (BR008) and records the
     * transition in the student's level history.
     *
     * @urlParam id integer required The exam registration ID. Example: 1
     */
    public function promote(PromoteExamResultRequest $request, $id, PromoteExamResultAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Xét lên cấp thành công.',
            fn ($studentLevel) => new StudentLevelResource($studentLevel),
        );
    }
}
