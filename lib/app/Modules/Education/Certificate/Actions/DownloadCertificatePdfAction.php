<?php

namespace App\Modules\Education\Certificate\Actions;

use App\Modules\Education\Certificate\Services\CertificateService;

class DownloadCertificatePdfAction
{
    public function handle(...$params)
    {
        return app(CertificateService::class)->downloadPdf(...$params);
    }
}
