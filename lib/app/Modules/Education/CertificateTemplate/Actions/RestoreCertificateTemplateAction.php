<?php

namespace App\Modules\Education\CertificateTemplate\Actions;

use App\Modules\Education\CertificateTemplate\Services\CertificateTemplateService;

class RestoreCertificateTemplateAction
{
    public function handle(...$params)
    {
        return app(CertificateTemplateService::class)->restore(...$params);
    }
}
