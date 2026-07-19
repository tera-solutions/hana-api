<?php

namespace App\Modules\Education\Certificate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Certificate\Http\Requests\IssueCertificateRequest;
use App\Modules\Education\Certificate\Http\Requests\RevokeCertificateRequest;
use App\Modules\Education\Certificate\Services\CertificateService;

/**
 * @group Education - Certificate
 *
 * Completion certificates (EDU-18). `verify` is intentionally outside
 * `auth.tera` — see Router/api.php.
 *
 * @authenticated
 */
class CertificateController extends Controller
{
    public function __construct(private CertificateService $certificates)
    {
    }

    /**
     * Roster with score/attendance/debt + current certificate, for the issue screen
     *
     * @urlParam classId integer required
     */
    public function eligibility($classId)
    {
        return $this->respondSuccess($this->certificates->eligibility($classId));
    }

    /**
     * Issue a certificate to one student
     *
     * @urlParam classId integer required
     */
    public function issue(IssueCertificateRequest $request, $classId)
    {
        return $this->tryRespond(
            fn () => $this->certificates->issue($classId, $request->validated('student_id')),
            'Đã phát hành chứng chỉ.',
        );
    }

    /**
     * All certificates issued for a class
     *
     * @urlParam classId integer required
     */
    public function list($classId)
    {
        return $this->respondSuccess($this->certificates->listByClass($classId));
    }

    /**
     * Revoke a certificate
     *
     * @urlParam id integer required
     */
    public function revoke(RevokeCertificateRequest $request, $id)
    {
        return $this->tryRespond(
            fn () => $this->certificates->revoke($id, $request->validated('reason')),
            'Đã thu hồi chứng chỉ.',
        );
    }

    /**
     * Public verification by QR token — no auth required
     *
     * @urlParam token string required
     */
    public function verify(string $token)
    {
        $result = $this->certificates->verify($token);

        if (! $result) {
            return $this->respondWithError('Không tìm thấy chứng chỉ.', [], 404);
        }

        return $this->respondSuccess($result);
    }
}
