<?php

namespace App\Modules\Finance\Promotion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'promotion_code' => $this->promotion_code,
            'promotion_name' => $this->promotion_name,
            'promotion_type' => $this->promotion_type,

            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'priority' => $this->priority,

            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'max_discount' => $this->max_discount,
            'bonus_lesson' => $this->bonus_lesson,
            'bonus_wallet_amount' => $this->bonus_wallet_amount,

            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,

            'rules' => $this->whenLoaded('rules'),
            'rewards' => $this->whenLoaded('rewards'),
            'vouchers' => VoucherResource::collection($this->whenLoaded('vouchers')),
            'vouchers_count' => $this->whenCounted('vouchers'),

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
