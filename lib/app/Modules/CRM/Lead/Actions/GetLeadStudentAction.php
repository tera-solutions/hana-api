<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadStudentService;

class GetLeadStudentAction
{
    public function handle(...$params)
    {
        return app(LeadStudentService::class)->find(...$params);
    }
}
