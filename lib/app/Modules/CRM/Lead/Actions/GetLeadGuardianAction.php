<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadGuardianService;

class GetLeadGuardianAction
{
    public function handle(...$params)
    {
        return app(LeadGuardianService::class)->find(...$params);
    }
}
