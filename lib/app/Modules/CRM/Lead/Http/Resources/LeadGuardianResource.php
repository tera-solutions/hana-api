<?php

namespace App\Modules\CRM\Lead\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadGuardianResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'lead_id' => $this->lead_id,

            'full_name' => $this->full_name,
            'relationship' => $this->relationship,
            'phone' => $this->phone,
            'email' => $this->email,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
