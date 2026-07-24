<?php

namespace App\Modules\Education\Certificate\Actions;

use App\Modules\Education\Certificate\Services\CertificateService;

class EligibleStudentsCertificateAction
{
    public function handle(...$params)
    {
        return app(CertificateService::class)->eligibleStudentsByCourse(...$params);
    }
}
