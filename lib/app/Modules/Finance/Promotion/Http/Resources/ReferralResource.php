<?php

namespace App\Modules\Finance\Promotion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReferralResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'referrer_parent_id' => $this->referrer_parent_id,
            'referred_parent_id' => $this->referred_parent_id,
            'promotion_id' => $this->promotion_id,
            'reward_amount' => $this->reward_amount,
            'status' => $this->status,
            'rewarded_at' => $this->rewarded_at,
            'created_at' => $this->created_at,
        ];
    }
}
