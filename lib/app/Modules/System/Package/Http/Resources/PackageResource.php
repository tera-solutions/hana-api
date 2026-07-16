<?php

namespace App\Modules\System\Package\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'package_code' => $this->package_code,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'billing_cycle' => $this->billing_cycle,
            'features' => $this->features,
            'feature_keys' => $this->feature_keys ?? [],
            'limits' => $this->limits,
            'badge' => $this->badge,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
