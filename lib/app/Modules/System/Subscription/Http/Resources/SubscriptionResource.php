<?php

namespace App\Modules\System\Subscription\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'package_id' => $this->package_id,
            'package' => $this->whenLoaded('package', fn () => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'price' => $this->package->price,
                'billing_cycle' => $this->package->billing_cycle,
                'features' => $this->package->features,
                'feature_keys' => $this->package->feature_keys ?? [],
                'limits' => $this->package->limits,
            ] : null),
            'price' => $this->price,
            'billing_cycle' => $this->billing_cycle,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'expires_at' => $this->expires_at,
        ];
    }
}
