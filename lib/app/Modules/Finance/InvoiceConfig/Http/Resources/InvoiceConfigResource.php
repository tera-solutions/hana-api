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
            'late_fee_enabled' => (bool) $this->late_fee_enabled,
            'late_fee_percent' => $this->late_fee_percent,
            'unpaid_student_status' => $this->unpaid_student_status,
            'reminder' => [
                'before_due_days' => $this->reminder_before_due_days,
                'on_overdue' => (bool) $this->reminder_on_overdue,
                'channels' => $this->reminder_channels ?? [],
            ],
            'updated_at' => $this->updated_at,
        ];
    }
}
