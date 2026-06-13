<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadService;

class UpdateLeadAction
{
    public function handle(...$params)
    {
        return app(LeadService::class)->update(...$params);
    }
}
