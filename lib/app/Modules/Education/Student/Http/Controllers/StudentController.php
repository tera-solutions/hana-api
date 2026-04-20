<?php

namespace App\Modules\Education\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Student\Actions\CreateStudentAction;
use App\Modules\Education\Student\Actions\UpdateStudentAction;
use App\Modules\Education\Student\Actions\DeleteStudentAction;
use App\Modules\Education\Student\Actions\GetStudentAction;
use App\Modules\Education\Student\Actions\ListStudentAction;
use App\Modules\Education\Student\Actions\ExportStudentAction;
use App\Modules\Education\Student\Http\Requests\CreateStudentRequest;
use App\Modules\Education\Student\Http\Requests\UpdateStudentRequest;
use App\Modules\Education\Student\Http\Requests\ExportStudentRequest;

/**
 * @group Education - Student
 */
class StudentController extends Controller
{
    public function list(ListStudentAction $action)
    {
        $data = $action->handle();
        return $this->respondSuccess($data);
    }

    public function create(CreateStudentRequest $request, CreateStudentAction $action)
    {
        $data = $action->handle($request->validated());

        return $this->respondSuccess($data);
    }

    public function detail($id, GetStudentAction $action)
    {
        $data = $action->handle($id);
        return $this->respondSuccess($data);
    }

    public function update(UpdateStudentRequest $request, $id, UpdateStudentAction $action)
    {
        $data = $action->handle($id, $request->validated());
        return $this->respondSuccess($data);
    }

    public function delete($id, DeleteStudentAction $action)
    {
        $data = $action->handle($id);
        return $this->respondSuccess($data);
    }

    public function export(ExportStudentRequest $request, ExportStudentAction $action)
    {
        $data = $action->handle($request->validated());

        return $this->respondSuccess($data);
    }
}