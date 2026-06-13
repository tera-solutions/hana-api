<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadStudentService;

class DeleteLeadStudentAction
{
    public function handle(...$params)
    {
        return app(LeadStudentService::class)->delete(...$params);
    }
}
