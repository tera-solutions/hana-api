<?php

namespace App\Modules\Education\Assignment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Assignment\Actions\GradeSubmissionAction;
use App\Modules\Education\Assignment\Actions\PublishSubmissionResultAction;
use App\Modules\Education\Assignment\Http\Requests\GradeSubmissionRequest;
use App\Modules\Education\Assignment\Http\Resources\AssignmentSubmissionResource;

/**
 * @group Education - Assignment Submission
 *
 * Grade and publish results of student submissions (assignment.md §9, §10).
 *
 * @authenticated
 */
class SubmissionController extends Controller
{
    /**
     * Grade a submission
     *
     * @urlParam id integer required The submission ID. Example: 1
     */
    public function grade(GradeSubmissionRequest $request, $id, GradeSubmissionAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Chấm bài thành công.',
            fn ($submission) => new AssignmentSubmissionResource($submission),
        );
    }

    /**
     * Publish a submission result
     *
     * @urlParam id integer required The submission ID. Example: 1
     */
    public function publish($id, PublishSubmissionResultAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Công bố kết quả thành công.',
            fn ($submission) => new AssignmentSubmissionResource($submission),
        );
    }
}
