<?php

namespace App\Modules\Education\Teacher\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Teacher\Actions\CreateTeacherAction;
use App\Modules\Education\Teacher\Actions\DeleteTeacherAction;
use App\Modules\Education\Teacher\Actions\GetTeacherAction;
use App\Modules\Education\Teacher\Actions\ListTeacherAction;
use App\Modules\Education\Teacher\Actions\UpdateTeacherAction;
use App\Modules\Education\Teacher\Http\Requests\CreateTeacherRequest;
use App\Modules\Education\Teacher\Http\Requests\UpdateTeacherRequest;

/**
 * @group Education - Teacher
 */
class TeacherController extends Controller
{
    public function list(ListTeacherAction $action)
    {
        $data = $action->handle();

        return $this->respondSuccess($data);
    }

    public function create(CreateTeacherRequest $request, CreateTeacherAction $action)
    {
        $data = $action->handle($request->validated());

        return $this->respondSuccess($data);
    }

    public function detail($id, GetTeacherAction $action)
    {
        $data = $action->handle($id);

        return $this->respondSuccess($data);
    }

    public function update(UpdateTeacherRequest $request, $id, UpdateTeacherAction $action)
    {
        $data = $action->handle($id, $request->validated());

        return $this->respondSuccess($data);
    }

    public function delete($id, DeleteTeacherAction $action)
    {
        $data = $action->handle($id);

        return $this->respondSuccess($data);
    }
}
