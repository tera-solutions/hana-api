<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadService;

class SuspendLeadAction
{
    public function handle(...$params)
    {
        return app(LeadService::class)->suspend(...$params);
    }
}
