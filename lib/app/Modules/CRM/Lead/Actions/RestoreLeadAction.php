<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadService;

class RestoreLeadAction
{
    public function handle(...$params)
    {
        return app(LeadService::class)->restore(...$params);
    }
}
