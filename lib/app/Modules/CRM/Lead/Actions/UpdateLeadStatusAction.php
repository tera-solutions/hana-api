<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadService;

class UpdateLeadStatusAction
{
    public function __construct(private LeadService $service) {}

    public function handle($id, array $data)
    {
        return $this->service->updateStatus($id, $data);
    }
}
