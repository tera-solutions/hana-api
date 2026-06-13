<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadStudentService;

class ListLeadStudentAction
{
    public function handle(...$params)
    {
        return app(LeadStudentService::class)->paginate(...$params);
    }
}
