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
use App\Modules\HR\Teacher\Http\Resources\TeacherResource;
use Illuminate\Http\Request;

/**
 * @group HR - Teacher
 */
class TeacherController extends Controller
{
    public function list(Request $request, ListTeacherAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), TeacherResource::class);
    }

    public function detail($id, GetTeacherAction $action)
    {
        $result = $action->handle($id);

        $data = [
            'teacher' => new TeacherResource($result['teacher']),
            'statistics' => $result['statistics'],
        ];

        return $this->respondSuccess($data);
    }

    public function create(CreateTeacherRequest $request, CreateTeacherAction $action)
    {
        $teacher = $action->handle($request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Tạo Teacher thành công.');
    }

    public function update(UpdateTeacherRequest $request, $id, UpdateTeacherAction $action)
    {
        $teacher = $action->handle($id, $request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Cập nhật Teacher thành công.');
    }

    public function delete($id, DeleteTeacherAction $action)
    {
        try {
            $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(null, 'Xóa Teacher thành công.');
    }
}
