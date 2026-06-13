<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadGuardianService;

class ListLeadGuardianAction
{
    public function handle(...$params)
    {
        return app(LeadGuardianService::class)->paginate(...$params);
    }
}
