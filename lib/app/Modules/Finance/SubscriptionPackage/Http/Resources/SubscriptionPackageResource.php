<?php

namespace App\Modules\Finance\SubscriptionPackage\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPackageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'sessions_included' => $this->sessions_included,
            'duration_days' => $this->duration_days,
            'applicable_courses' => $this->applicable_courses,
            'status' => $this->status,
            'discount_rules' => $this->whenLoaded('discountRules', fn () => $this->discountRules->map(fn ($rule) => [
                'id' => $rule->id,
                'type' => $rule->type,
                'value' => $rule->value,
                'condition' => $rule->condition,
                'enabled' => (bool) $rule->enabled,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
