<?php

namespace App\Modules\Education\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Student\Actions\CreateStudentAction;
use App\Modules\Education\Student\Actions\DeleteStudentAction;
use App\Modules\Education\Student\Actions\ExportStudentAction;
use App\Modules\Education\Student\Actions\GetStudentAction;
use App\Modules\Education\Student\Actions\ListStudentAction;
use App\Modules\Education\Student\Actions\RestoreStudentAction;
use App\Modules\Education\Student\Actions\SuspendStudentAction;
use App\Modules\Education\Student\Actions\UpdateStudentAction;
use App\Modules\Education\Student\Http\Requests\CreateStudentRequest;
use App\Modules\Education\Student\Http\Requests\ExportStudentRequest;
use App\Modules\Education\Student\Http\Requests\SuspendStudentRequest;
use App\Modules\Education\Student\Http\Requests\UpdateStudentRequest;
use App\Modules\Education\Student\Http\Resources\StudentResource;
use Illuminate\Http\Request;

/**
 * @group Education - Student
 *
 * Manage students (enrollment lifecycle).
 */
class StudentController extends Controller
{
    public function list(Request $request, ListStudentAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), StudentResource::class);
    }

    public function detail($id, GetStudentAction $action)
    {
        $result = $action->handle($id);

        return $this->respondSuccess([
            'student' => new StudentResource($result['student']),
            'statistics' => $result['statistics'],
        ]);
    }

    public function create(CreateStudentRequest $request, CreateStudentAction $action)
    {
        $student = $action->handle($request->validated());

        return $this->respondSuccess(new StudentResource($student), 'Tạo học viên thành công.');
    }

    public function update(UpdateStudentRequest $request, $id, UpdateStudentAction $action)
    {
        $student = $action->handle($id, $request->validated());

        return $this->respondSuccess(new StudentResource($student), 'Cập nhật học viên thành công.');
    }

    public function suspend(SuspendStudentRequest $request, $id, SuspendStudentAction $action)
    {
        try {
            $student = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new StudentResource($student), 'Ngừng học viên thành công.');
    }

    public function restore($id, RestoreStudentAction $action)
    {
        try {
            $student = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new StudentResource($student), 'Khôi phục học viên thành công.');
    }

    public function delete($id, DeleteStudentAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa học viên thành công.');
    }

    public function export(ExportStudentRequest $request, ExportStudentAction $action)
    {
        return $this->respondSuccess($action->handle($request->validated()));
    }
}
