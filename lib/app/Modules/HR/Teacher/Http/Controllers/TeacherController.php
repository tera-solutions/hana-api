<?php

namespace App\Modules\HR\Teacher\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Teacher\Actions\CreateTeacherAction;
use App\Modules\HR\Teacher\Actions\GetTeacherAction;
use App\Modules\HR\Teacher\Actions\ListTeacherAction;
use App\Modules\HR\Teacher\Actions\ResignTeacherAction;
use App\Modules\HR\Teacher\Actions\RestoreTeacherAction;
use App\Modules\HR\Teacher\Actions\SuspendTeacherAction;
use App\Modules\HR\Teacher\Actions\UpdateTeacherAction;
use App\Modules\HR\Teacher\Http\Requests\CreateTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\ResignTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\SuspendTeacherRequest;
use App\Modules\HR\Teacher\Http\Requests\UpdateTeacherRequest;
use App\Modules\HR\Teacher\Http\Resources\TeacherResource;
use Illuminate\Http\Request;

/**
 * @group HR - Teacher
 *
 * Manage teachers (full lifecycle, specialisations, certificates).
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

        return $this->respondSuccess([
            'teacher' => new TeacherResource($result['teacher']),
            'statistics' => $result['statistics'],
        ]);
    }

    public function create(CreateTeacherRequest $request, CreateTeacherAction $action)
    {
        $teacher = $action->handle($request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Tạo giáo viên thành công.');
    }

    public function update(UpdateTeacherRequest $request, $id, UpdateTeacherAction $action)
    {
        $teacher = $action->handle($id, $request->validated());

        return $this->respondSuccess(new TeacherResource($teacher), 'Cập nhật giáo viên thành công.');
    }

    public function suspend(SuspendTeacherRequest $request, $id, SuspendTeacherAction $action)
    {
        try {
            $teacher = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new TeacherResource($teacher), 'Ngừng giáo viên thành công.');
    }

    public function restore($id, RestoreTeacherAction $action)
    {
        try {
            $teacher = $action->handle($id);
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new TeacherResource($teacher), 'Khôi phục giáo viên thành công.');
    }

    public function resign(ResignTeacherRequest $request, $id, ResignTeacherAction $action)
    {
        try {
            $teacher = $action->handle($id, $request->validated());
        } catch (\RuntimeException $e) {
            return $this->respondWithError($e->getMessage());
        }

        return $this->respondSuccess(new TeacherResource($teacher), 'Cho giáo viên nghỉ việc thành công.');
    }
}
