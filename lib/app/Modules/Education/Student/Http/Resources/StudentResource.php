<?php

namespace App\Modules\Education\Student\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'dob' => $this->dob,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'language' => $this->language,

            'email' => $this->email,
            'phone' => $this->phone,

            'level' => $this->level,
            'status' => $this->status,
            'enrollment_date' => $this->enrollment_date,
            'admission_source' => $this->admission_source,

            'user_id' => $this->user_id,

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

            'profile' => $this->whenLoaded('profile', fn () => [
                'address' => $this->profile?->address,
                'province' => $this->profile?->province,
                'district' => $this->profile?->district,
                'school' => $this->profile?->school,
                'grade' => $this->profile?->grade,
                'note' => $this->profile?->note,
            ]),

            'parents' => $this->whenLoaded('parents', fn () => $this->parents->map(fn ($parent) => [
                'id' => $parent->id,
                'name' => $parent->name,
                'phone' => $parent->phone,
                'email' => $parent->email,
                'relation' => $parent->pivot->relation,
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
