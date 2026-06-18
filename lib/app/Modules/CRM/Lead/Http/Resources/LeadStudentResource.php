<?php

namespace App\Modules\CRM\Lead\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadStudentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'lead_id' => $this->lead_id,
            'lead' => $this->whenLoaded('lead', fn () => [
                'id' => $this->lead?->id,
                'name' => $this->lead?->name,
                'phone' => $this->lead?->phone,
                'email' => $this->lead?->email,
                'status' => $this->lead?->status,
            ]),

            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student?->id,
                'code' => $this->student?->code,
                'name' => $this->student?->name,
                'level_id' => $this->student?->level_id,
                'status' => $this->student?->status,
            ]),

            'relationship' => $this->relationship,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
