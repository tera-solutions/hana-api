<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadStudentService;

class CreateLeadStudentAction
{
    public function handle(...$params)
    {
        return app(LeadStudentService::class)->create(...$params);
    }
}
