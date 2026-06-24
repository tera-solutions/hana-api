<?php

namespace App\Modules\Finance\Wallet\Http\Resources;

use App\Modules\Finance\Wallet\Enums\WalletTransactionType;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transaction_code,
            'wallet_id' => $this->wallet_id,

            'transaction_type' => $this->transaction_type,
            'transaction_type_label' => WalletTransactionType::tryFrom((string) $this->transaction_type)?->label(),

            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,

            'amount' => $this->amount,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,

            'description' => $this->description,

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
