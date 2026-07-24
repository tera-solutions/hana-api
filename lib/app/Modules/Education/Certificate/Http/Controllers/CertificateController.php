<?php

namespace App\Modules\Education\Certificate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\Certificate\Actions\BulkIssueCertificateAction;
use App\Modules\Education\Certificate\Actions\DownloadCertificatePdfAction;
use App\Modules\Education\Certificate\Actions\EligibleStudentsCertificateAction;
use App\Modules\Education\Certificate\Actions\SummaryCertificateAction;
use App\Modules\Education\Certificate\Http\Requests\BulkIssueCertificateRequest;
use App\Modules\Education\Certificate\Http\Requests\IssueCertificateRequest;
use App\Modules\Education\Certificate\Http\Requests\RevokeCertificateRequest;
use App\Modules\Education\Certificate\Http\Resources\CertificateResource;
use App\Modules\Education\Certificate\Services\CertificateService;
use Illuminate\Http\Request;

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
    public function __construct(private CertificateService $certificates) {}

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
    public function listByClass($classId)
    {
        return $this->respondSuccess($this->certificates->listByClass($classId));
    }

    /**
     * All certificates issued to a student, across classes
     *
     * @urlParam studentId integer required
     */
    public function listByStudent($studentId)
    {
        return $this->respondSuccess($this->certificates->listByStudent($studentId));
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

    /**
     * Summary + paginated, filterable list of all issued certificates
     *
     * @queryParam template_id integer Filter by template. Example: 1
     * @queryParam course_id integer Filter by course. Example: 1
     * @queryParam status string Filter: issued|revoked. Example: issued
     * @queryParam search string Match student name. Example: Minh
     */
    public function list(Request $request, SummaryCertificateAction $action)
    {
        $result = $action->handle($request->all());
        $paginator = $result['paginator'];

        return $this->respondSuccess([
            'summary' => $result['summary'],
            'items' => CertificateResource::collection($paginator->items()),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Students of a course eligible for bulk certificate issuance
     *
     * @queryParam course_id integer required
     * @queryParam threshold number Minimum completion rate (default 100). Example: 100
     */
    public function eligibleStudents(Request $request, EligibleStudentsCertificateAction $action)
    {
        $courseId = (int) $request->input('course_id');
        $threshold = (float) $request->input('threshold', 100);

        return $this->respondSuccess($action->handle($courseId, $threshold));
    }

    /**
     * Issue certificates to multiple students of a course at once
     */
    public function issueBulk(BulkIssueCertificateRequest $request, BulkIssueCertificateAction $action)
    {
        $data = $request->validated();

        return $this->respondSuccess(
            $action->handle($data['course_id'], $data['student_ids'], $data['template_id']),
            'Đã cấp chứng nhận.',
        );
    }

    /**
     * Download a certificate as PDF
     *
     * @urlParam id integer required
     */
    public function download($id, DownloadCertificatePdfAction $action)
    {
        return $action->handle($id)->download("certificate-{$id}.pdf");
    }
}
