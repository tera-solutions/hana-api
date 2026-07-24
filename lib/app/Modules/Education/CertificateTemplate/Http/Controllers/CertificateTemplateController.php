<?php

namespace App\Modules\Education\CertificateTemplate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Education\CertificateTemplate\Actions\CreateCertificateTemplateAction;
use App\Modules\Education\CertificateTemplate\Actions\GetCertificateTemplateAction;
use App\Modules\Education\CertificateTemplate\Actions\ListCertificateTemplateAction;
use App\Modules\Education\CertificateTemplate\Actions\RestoreCertificateTemplateAction;
use App\Modules\Education\CertificateTemplate\Actions\SuspendCertificateTemplateAction;
use App\Modules\Education\CertificateTemplate\Actions\UpdateCertificateTemplateAction;
use App\Modules\Education\CertificateTemplate\Http\Requests\CreateCertificateTemplateRequest;
use App\Modules\Education\CertificateTemplate\Http\Requests\UpdateCertificateTemplateRequest;
use App\Modules\Education\CertificateTemplate\Http\Resources\CertificateTemplateResource;
use Illuminate\Http\Request;

/**
 * @group Education - Certificate Template
 *
 * Reusable certificate designs (teacher-app-076) picked from when issuing a
 * certificate. `preview_image` is a path/URL to an already-uploaded image.
 *
 * @authenticated
 */
class CertificateTemplateController extends Controller
{
    /**
     * List certificate templates
     *
     * @queryParam search string Match template name. Example: Mẫu A
     * @queryParam status string Filter: active or inactive. Example: active
     */
    public function list(Request $request, ListCertificateTemplateAction $action)
    {
        return $this->respondPaginated($action->handle($request->all()), CertificateTemplateResource::class);
    }

    /**
     * Certificate template detail
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function detail($id, GetCertificateTemplateAction $action)
    {
        return $this->respondSuccess(new CertificateTemplateResource($action->handle($id)));
    }

    /**
     * Create certificate template
     */
    public function create(CreateCertificateTemplateRequest $request, CreateCertificateTemplateAction $action)
    {
        $template = $action->handle($request->validated());

        return $this->respondSuccess(new CertificateTemplateResource($template), 'Tạo mẫu chứng nhận thành công.');
    }

    /**
     * Update certificate template
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function update(UpdateCertificateTemplateRequest $request, $id, UpdateCertificateTemplateAction $action)
    {
        $template = $action->handle($id, $request->validated());

        return $this->respondSuccess(new CertificateTemplateResource($template), 'Cập nhật mẫu chứng nhận thành công.');
    }

    /**
     * Suspend certificate template
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function suspend($id, SuspendCertificateTemplateAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Ngừng sử dụng mẫu chứng nhận thành công.',
            fn ($template) => new CertificateTemplateResource($template),
        );
    }

    /**
     * Restore certificate template
     *
     * @urlParam id integer required The template ID. Example: 1
     */
    public function restore($id, RestoreCertificateTemplateAction $action)
    {
        return $this->tryRespond(
            fn () => $action->handle($id),
            'Khôi phục mẫu chứng nhận thành công.',
            fn ($template) => new CertificateTemplateResource($template),
        );
    }
}
