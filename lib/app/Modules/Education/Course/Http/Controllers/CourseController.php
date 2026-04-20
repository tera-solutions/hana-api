<?php

namespace App\Modules\Education\Course\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Course\Actions\CreateCourseAction;
use App\Modules\Education\Course\Actions\UpdateCourseAction;
use App\Modules\Education\Course\Actions\DeleteCourseAction;
use App\Modules\Education\Course\Actions\GetCourseAction;
use App\Modules\Education\Course\Actions\ListCourseAction;
use App\Modules\Education\Course\Http\Requests\CreateCourseRequest;
use App\Modules\Education\Course\Http\Requests\UpdateCourseRequest;

/**
 * @group Education - Course
 */
class CourseController extends Controller
{
    public function list(ListCourseAction $action)
    {
        $data = $action->handle();
        return $this->respondSuccess($data);
    }

    public function create(CreateCourseRequest $request, CreateCourseAction $action)
    {
        $data = $action->handle($request->validated());
        return $this->respondSuccess($data);
    }

    public function detail($id, GetCourseAction $action)
    {
        $data = $action->handle($id);
        return $this->respondSuccess($data);
    }

    public function update(UpdateCourseRequest $request, $id, UpdateCourseAction $action)
    {
        $data = $action->handle($id, $request->validated());
        return $this->respondSuccess($data);
    }

    public function delete($id, DeleteCourseAction $action)
    {
        $data = $action->handle($id);
        return $this->respondSuccess($data);
    }
}