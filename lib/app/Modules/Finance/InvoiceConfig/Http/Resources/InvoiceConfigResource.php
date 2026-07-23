<?php

namespace App\Modules\Finance\InvoiceConfig\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceConfigResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'auto_generate' => (bool) $this->auto_generate,
            'billing_day' => $this->billing_day,
            'due_days' => $this->due_days,
            'updated_at' => $this->updated_at,
        ];
    }
}
