<?php

namespace App\Modules\CRM\Lead\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'gender' => $this->gender,
            'dob' => $this->dob,

            'email' => $this->email,
            'phone' => $this->phone,

            'source' => $this->source,
            'status' => $this->status,
            'note' => $this->note,
            'next_appointment' => $this->next_appointment,

            'previous_status' => $this->previous_status,
            'suspended_at' => $this->suspended_at,
            'suspend_reason' => $this->suspend_reason,
            'suspended_by' => $this->suspended_by,

            'guardians_count' => $this->when($this->guardians_count !== null, $this->guardians_count),
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

            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
                'avatar' => $this->owner?->avatar,
            ]),

            'guardians' => $this->whenLoaded('guardians', fn () => $this->guardians->map(fn ($guardian) => [
                'id' => $guardian->id,
                'full_name' => $guardian->full_name,
                'relationship' => $guardian->relationship,
                'phone' => $guardian->phone,
                'email' => $guardian->email,
            ])),

            'students' => $this->whenLoaded('students', fn () => $this->students->map(fn ($student) => [
                'id' => $student->id,
                'code' => $student->code,
                'name' => $student->name,
                'relationship' => $student->pivot->relationship,
            ])),

            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])),

            'courses' => $this->whenLoaded('courses', fn () => $this->courses->map(fn ($course) => [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
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
