<?php

namespace App\Modules\Education\Certificate\Actions;

use App\Modules\Education\Certificate\Services\CertificateService;

class SummaryCertificateAction
{
    public function handle(...$params)
    {
        return app(CertificateService::class)->summary(...$params);
    }
}
