<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadService;

class ListLeadAction
{
    public function handle(...$params)
    {
        return app(LeadService::class)->paginate(...$params);
    }
}
