<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadService;

class AddLeadHistoryAction
{
    public function handle($id, array $data)
    {
        return app(LeadService::class)->addHistory($id, $data);
    }
}
