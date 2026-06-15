<?php

namespace App\Modules\Finance\Debt\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DebtAdjustmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'adjustment_type' => $this->adjustment_type,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'note' => $this->note,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
