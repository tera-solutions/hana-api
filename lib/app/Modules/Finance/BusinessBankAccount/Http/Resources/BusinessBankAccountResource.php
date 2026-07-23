<?php

namespace App\Modules\Finance\BusinessBankAccount\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessBankAccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,

            'bank_name' => $this->bank_name,
            'bank_code' => $this->bank_code,
            'account_number' => $this->account_number,
            'account_holder' => $this->account_holder,
            'branch' => $this->branch,

            'is_default' => (bool) $this->is_default,
            'status' => $this->status,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
