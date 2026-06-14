<?php

namespace App\Modules\HR\Teacher\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'full_name' => $this->full_name,
            'avatar' => $this->avatar,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'email' => $this->email,
            'phone' => $this->phone,
            'identity_no' => $this->identity_no,
            'address' => $this->address,

            'teacher_type' => $this->teacher_type,
            'employment_type' => $this->employment_type,
            'hourly_rate' => $this->hourly_rate,
            'monthly_salary' => $this->monthly_salary,
            'bank_account' => $this->whenLoaded('bankAccount', fn () => $this->bankAccount ? [
                'bank_name' => $this->bankAccount->bank_name,
                'bank_account_number' => $this->bankAccount->bank_account_number,
                'bank_account_holder' => $this->bankAccount->bank_account_holder,
                'bank_branch' => $this->bankAccount->bank_branch,
            ] : null),
            'status' => $this->status,
            'joined_at' => $this->joined_at,
            'resigned_at' => $this->resigned_at,
            'note' => $this->note,

            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),

            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager?->id,
                'name' => $this->manager?->full_name,
            ]),

            'skills' => $this->whenLoaded('skills', fn () => $this->skills->map(fn ($skill) => [
                'id' => $skill->id,
                'skill_name' => $skill->skill_name,
                'level' => $skill->level,
            ])),

            'certificates' => $this->whenLoaded('certificates', fn () => TeacherCertificateResource::collection($this->certificates)),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
