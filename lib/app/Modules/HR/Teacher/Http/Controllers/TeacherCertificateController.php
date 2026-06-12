<?php

namespace App\Modules\HR\Teacher\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Teacher\Http\Requests\CreateCertificateRequest;
use App\Modules\HR\Teacher\Http\Requests\UpdateCertificateRequest;
use App\Modules\HR\Teacher\Http\Resources\TeacherCertificateResource;
use App\Modules\HR\Teacher\Services\TeacherCertificateService;

/**
 * @group HR - Teacher Certificate
 *
 * Manage a teacher's professional certificates.
 */
class TeacherCertificateController extends Controller
{
    public function list($teacherId, TeacherCertificateService $service)
    {
        return $this->respondSuccess(
            TeacherCertificateResource::collection($service->listFor($teacherId))
        );
    }

    public function create(CreateCertificateRequest $request, $teacherId, TeacherCertificateService $service)
    {
        $certificate = $service->create($teacherId, $request->validated());

        return $this->respondSuccess(new TeacherCertificateResource($certificate), 'Thêm chứng chỉ thành công.');
    }

    public function update(UpdateCertificateRequest $request, $id, TeacherCertificateService $service)
    {
        $certificate = $service->update($id, $request->validated());

        return $this->respondSuccess(new TeacherCertificateResource($certificate), 'Cập nhật chứng chỉ thành công.');
    }

    public function delete($id, TeacherCertificateService $service)
    {
        $service->delete($id);

        return $this->respondSuccess(null, 'Xóa chứng chỉ thành công.');
    }
}
