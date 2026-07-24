<?php

namespace App\Modules\Education\Certificate\Actions;

use App\Modules\Education\Certificate\Services\CertificateService;

class BulkIssueCertificateAction
{
    public function handle(...$params)
    {
        return app(CertificateService::class)->bulkIssue(...$params);
    }
}
