<?php

namespace App\Modules\Education\Level\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LevelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'business' => $this->whenLoaded('business', fn () => $this->business ? [
                'id' => $this->business->id,
                'name' => $this->business->name,
            ] : null),
            'level_code' => $this->level_code,
            'level_name' => $this->level_name,
            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
            ] : null),
            'level_order' => $this->level_order,
            'cefr_level' => $this->cefr_level,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
