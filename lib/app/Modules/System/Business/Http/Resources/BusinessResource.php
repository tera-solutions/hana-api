<?php

namespace App\Modules\System\Business\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_code' => $this->business_code,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'prefix' => $this->prefix,
            'tax_code' => $this->tax_code,
            'website' => $this->website,
            'logo' => $this->logo,

            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'province' => $this->province,
            'district' => $this->district,
            'ward' => $this->ward,
            'zip_code' => $this->zip_code,

            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager?->id,
                'name' => $this->manager?->name,
            ]),
            'status' => $this->status,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
