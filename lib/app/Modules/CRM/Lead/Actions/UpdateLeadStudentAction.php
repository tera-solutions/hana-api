<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadStudentService;

class UpdateLeadStudentAction
{
    public function handle(...$params)
    {
        return app(LeadStudentService::class)->update(...$params);
    }
}
