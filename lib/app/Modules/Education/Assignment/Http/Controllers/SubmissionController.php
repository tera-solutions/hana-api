<?php

namespace App\Modules\Education\Assignment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Assignment\Actions\GetSubmissionAction;
use App\Modules\Education\Assignment\Actions\GradeSubmissionAction;
use App\Modules\Education\Assignment\Actions\ListGradedSubmissionsAction;
use App\Modules\Education\Assignment\Actions\ListSubmittedSubmissionsAction;
use App\Modules\Education\Assignment\Actions\PublishSubmissionResultAction;
use App\Modules\Education\Assignment\Actions\UpdateSubmissionGradeAction;
use App\Modules\Education\Assignment\Http\Requests\GradeSubmissionRequest;
use App\Modules\Education\Assignment\Http\Requests\UpdateSubmissionGradeRequest;
use App\Modules\Education\Assignment\Http\Resources\AssignmentSubmissionResource;
use Illuminate\Http\Request;

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
     * List students who submitted
     *
     * Every student who has submitted this assignment, whatever the grading status
     * (submitted / late / graded / reviewed). Merely-assigned students who never
     * submitted are excluded.
     *
     * @urlParam assignmentId integer required The assignment ID. Example: 1
     *
     * @queryParam sort_by string Sortable: submitted_at, status, created_at. No-example
     * @queryParam sort_dir string asc or desc. No-example
     * @queryParam per_page integer 20, 50 or 100. No-example
     * @queryParam page integer Page number. No-example
     */
    public function submitted($assignmentId, Request $request, ListSubmittedSubmissionsAction $action)
    {
        return $this->respondPaginated(
            $action->handle($assignmentId, $request->all()),
            AssignmentSubmissionResource::class,
        );
    }

    /**
     * List graded submissions
     *
     * Submissions of an assignment that have been graded (status graded / reviewed).
     *
     * @urlParam assignmentId integer required The assignment ID. Example: 1
     *
     * @queryParam sort_by string Sortable: score, submitted_at, updated_at, created_at. No-example
     * @queryParam sort_dir string asc or desc. No-example
     * @queryParam per_page integer 20, 50 or 100. No-example
     * @queryParam page integer Page number. No-example
     */
    public function graded($assignmentId, Request $request, ListGradedSubmissionsAction $action)
    {
        return $this->respondPaginated(
            $action->handle($assignmentId, $request->all()),
            AssignmentSubmissionResource::class,
        );
    }

    /**
     * Graded submission detail
     *
     * @urlParam id integer required The submission ID. Example: 1
     */
    public function detail($id, GetSubmissionAction $action)
    {
        return $this->respondSuccess(new AssignmentSubmissionResource($action->handle($id)));
    }

    /**
     * Update a submission grade
     *
     * Edit the score/comment of an already-graded submission.
     *
     * @urlParam id integer required The submission ID. Example: 1
     */
    public function update(UpdateSubmissionGradeRequest $request, $id, UpdateSubmissionGradeAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id, $request->validated()),
            'Cập nhật điểm thành công.',
            fn ($submission) => new AssignmentSubmissionResource($submission),
        );
    }

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
