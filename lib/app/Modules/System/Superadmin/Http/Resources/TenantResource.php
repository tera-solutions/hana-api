<?php

namespace App\Modules\System\Superadmin\Http\Resources;

use App\Modules\System\Subscription\Http\Resources\SubscriptionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'business_code' => $this->business_code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'owner' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'full_name' => $this->manager->full_name,
                'email' => $this->manager->email,
            ] : null),
            'subscription' => $this->whenLoaded(
                'currentSubscription',
                fn () => $this->currentSubscription
                    ? new SubscriptionResource($this->currentSubscription)
                    : null,
            ),
        ];
    }
}
