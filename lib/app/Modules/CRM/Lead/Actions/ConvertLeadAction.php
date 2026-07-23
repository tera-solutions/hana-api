<?php

namespace App\Modules\CRM\Lead\Actions;

use App\Modules\CRM\Lead\Services\LeadService;

class ConvertLeadAction
{
    public function __construct(private LeadService $service) {}

    public function handle($id, array $data)
    {
        return $this->service->convert($id, $data);
    }
}
