<?php

namespace App\Modules\HR\Teacher\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Teacher\Actions\CreateTeacherAction;
use App\Modules\HR\Teacher\Actions\DeleteTeacherAction;
use App\Modules\HR\Teacher\Actions\GetTeacherAction;
use App\Modules\HR\Teacher\Actions\ListTeacherAction;
use App\Modules\HR\Teacher\Actions\UpdateTeacherAction;
use App\Modules\HR\Teacher\Http\Requests\CreateTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\UpdateTeacherRequest;

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
