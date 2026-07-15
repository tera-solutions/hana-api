<?php

namespace App\Modules\Education\CourseCurriculum\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseCurriculumResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course?->id,
                'code' => $this->course?->code,
                'name' => $this->course?->name,
            ]),
            'title' => $this->title,
            'order' => $this->order,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
