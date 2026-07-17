<?php

namespace App\Modules\Finance\BankAccount\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_holder' => $this->bank_account_holder,
            'bank_branch' => $this->bank_branch,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
