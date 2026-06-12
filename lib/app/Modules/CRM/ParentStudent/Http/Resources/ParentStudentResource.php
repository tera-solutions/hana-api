<?php

namespace App\Modules\CRM\ParentStudent\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ParentStudentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', fn () => [
                'id' => $this->parent?->id,
                'code' => $this->parent?->code,
                'name' => $this->parent?->name,
                'phone' => $this->parent?->phone,
                'email' => $this->parent?->email,
                'status' => $this->parent?->status,
            ]),

            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student?->id,
                'code' => $this->student?->code,
                'name' => $this->student?->name,
                'level' => $this->student?->level,
                'status' => $this->student?->status,
            ]),

            'relation' => $this->relation,
            'is_primary_contact' => $this->is_primary_contact,
            'is_billing_contact' => $this->is_billing_contact,
            'is_pickup_authorized' => $this->is_pickup_authorized,
            'note' => $this->note,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
