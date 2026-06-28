<?php

namespace App\Modules\Education\LessonPlan\Http\Resources;

use App\Modules\Education\LessonPlanLesson\Http\Resources\LessonPlanLessonResource;
use App\Modules\Education\LessonPlanVersion\Http\Resources\LessonPlanVersionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonPlanResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'plan_code' => $this->plan_code,
            'plan_name' => $this->plan_name,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'course_id' => $this->course_id,
            'level_id' => $this->level_id,
            'version' => $this->version,
            'total_lessons' => $this->total_lessons,
            'description' => $this->description,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'published_by' => $this->published_by,

            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course?->id,
                'name' => $this->course?->name,
                'code' => $this->course?->code,
            ]),
            'lessons' => LessonPlanLessonResource::collection($this->whenLoaded('lessons')),
            'versions' => LessonPlanVersionResource::collection($this->whenLoaded('versions')),
            'lessons_count' => $this->whenCounted('lessons'),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
