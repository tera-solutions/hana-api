<?php

namespace App\Modules\CRM\Parent\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ParentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'gender' => $this->gender,
            'dob' => $this->dob,

            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'province' => $this->province,
            'district' => $this->district,

            'occupation' => $this->occupation,
            'company' => $this->company,
            'note' => $this->note,
            'status' => $this->status,

            'students_count' => $this->when($this->students_count !== null, $this->students_count),

            'business_id' => $this->business_id,
            'business' => $this->whenLoaded('business', fn () => [
                'id' => $this->business?->id,
                'name' => $this->business?->name,
            ]),

            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),

            'students' => $this->whenLoaded('students', fn () => $this->students->map(fn ($student) => [
                'id' => $student->id,
                'code' => $student->code,
                'name' => $student->name,
                'relation' => $student->pivot->relation,
            ])),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
