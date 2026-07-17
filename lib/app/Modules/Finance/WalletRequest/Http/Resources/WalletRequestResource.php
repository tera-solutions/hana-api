<?php

namespace App\Modules\Finance\WalletRequest\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletRequestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'wallet_id' => $this->wallet_id,
            'request_type' => $this->request_type,
            'amount' => $this->amount,
            'status' => $this->status,
            'note' => $this->note,

            'bank_account' => $this->whenLoaded('bankAccount', fn () => $this->bankAccount ? [
                'id' => $this->bankAccount->id,
                'bank_name' => $this->bankAccount->bank_name,
                'bank_account_number' => $this->bankAccount->bank_account_number,
                'bank_account_holder' => $this->bankAccount->bank_account_holder,
                'bank_branch' => $this->bankAccount->bank_branch,
            ] : null),

            'reject_reason' => $this->reject_reason,

            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,

            'completed_by' => $this->completed_by,
            'completed_at' => $this->completed_at,
            'wallet_transaction_id' => $this->wallet_transaction_id,

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
