<?php

namespace App\Modules\HR\Achievement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Achievement\Actions\CreateTeacherReviewAction;
use App\Modules\HR\Achievement\Actions\ListTeacherReviewAction;
use App\Modules\HR\Achievement\Http\Requests\CreateTeacherReviewRequest;
use App\Modules\HR\Achievement\Http\Resources\TeacherReviewResource;
use Illuminate\Http\Request;

/**
 * @group HR - Teacher Review
 *
 * Parent/student ratings of a teacher.
 *
 * @authenticated
 */
class TeacherReviewController extends Controller
{
    /**
     * List the authenticated teacher's reviews
     */
    public function list(Request $request, ListTeacherReviewAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), TeacherReviewResource::class);
    }

    /**
     * Submit a teacher review
     */
    public function create(CreateTeacherReviewRequest $request, CreateTeacherReviewAction $action)
    {
        return $this->respondSuccess(
            new TeacherReviewResource($action->handle($request->validated())),
            'Gửi đánh giá thành công.',
        );
    }
}
