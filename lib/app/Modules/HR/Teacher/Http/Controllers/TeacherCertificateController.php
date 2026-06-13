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
 *
 * @authenticated
 */
class TeacherCertificateController extends Controller
{
    /**
     * List certificates for a teacher
     *
     * @urlParam teacherId integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": [
     *     {
     *       "id": 1, "teacher_id": 1,
     *       "certificate_name": "IELTS 8.0", "issuer": "British Council",
     *       "issued_date": "2024-03-01", "expired_date": "2027-03-01", "attachment": null,
     *       "is_expired": false, "is_expiring_soon": false,
     *       "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-01T08:00:00.000000Z"
     *     }
     *   ],
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function list($teacherId, TeacherCertificateService $service)
    {
        return $this->respondSuccess(
            TeacherCertificateResource::collection($service->listFor($teacherId))
        );
    }

    /**
     * Add certificate to a teacher
     *
     * @urlParam teacherId integer required The teacher ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thêm chứng chỉ thành công.",
     *   "data": {
     *     "id": 2, "teacher_id": 1,
     *     "certificate_name": "TOEIC 900", "issuer": "ETS",
     *     "issued_date": "2025-01-15", "expired_date": "2027-01-15", "attachment": null,
     *     "is_expired": false, "is_expiring_soon": false,
     *     "created_at": "2026-06-13T08:00:00.000000Z", "updated_at": "2026-06-13T08:00:00.000000Z"
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function create(CreateCertificateRequest $request, $teacherId, TeacherCertificateService $service)
    {
        $certificate = $service->create($teacherId, $request->validated());

        return $this->respondSuccess(new TeacherCertificateResource($certificate), 'Thêm chứng chỉ thành công.');
    }

    /**
     * Update certificate
     *
     * @urlParam id integer required The certificate ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Cập nhật chứng chỉ thành công.",
     *   "data": {
     *     "id": 1, "teacher_id": 1,
     *     "certificate_name": "IELTS 8.5", "issuer": "British Council",
     *     "issued_date": "2024-03-01", "expired_date": "2027-03-01", "attachment": null,
     *     "is_expired": false, "is_expiring_soon": false,
     *     "created_at": "2026-06-01T08:00:00.000000Z", "updated_at": "2026-06-13T09:00:00.000000Z"
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function update(UpdateCertificateRequest $request, $id, TeacherCertificateService $service)
    {
        $certificate = $service->update($id, $request->validated());

        return $this->respondSuccess(new TeacherCertificateResource($certificate), 'Cập nhật chứng chỉ thành công.');
    }

    /**
     * Delete certificate
     *
     * @urlParam id integer required The certificate ID. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Xóa chứng chỉ thành công.",
     *   "data": null,
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function delete($id, TeacherCertificateService $service)
    {
        $service->delete($id);

        return $this->respondSuccess(null, 'Xóa chứng chỉ thành công.');
    }
}
