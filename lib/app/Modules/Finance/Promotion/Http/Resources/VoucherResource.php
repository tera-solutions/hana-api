<?php

namespace App\Modules\Finance\Promotion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'promotion_id' => $this->promotion_id,
            'voucher_code' => $this->voucher_code,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'expired_at' => $this->expired_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
