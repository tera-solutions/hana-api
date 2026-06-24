<?php

namespace App\Modules\Finance\Wallet\Http\Resources;

use App\Modules\Finance\Wallet\Enums\WalletOwnerType;
use App\Modules\Finance\Wallet\Enums\WalletStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'wallet_code' => $this->wallet_code,

            'owner_type' => $this->owner_type,
            'owner_type_label' => WalletOwnerType::tryFrom((string) $this->owner_type)?->label(),
            'owner_id' => $this->owner_id,

            'available_balance' => $this->available_balance,
            'bonus_balance' => $this->bonus_balance,
            'frozen_balance' => $this->frozen_balance,
            'currency' => $this->currency,

            'status' => $this->status,
            'status_label' => WalletStatus::tryFrom((string) $this->status)?->label(),

            'transactions' => WalletTransactionResource::collection($this->whenLoaded('transactions')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
