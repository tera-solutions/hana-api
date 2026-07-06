<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Resources;

use App\Modules\Education\LessonPlanMaterial\Http\Resources\LessonPlanMaterialResource;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonPlanLessonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'lesson_plan_id' => $this->lesson_plan_id,
            'lesson_no' => $this->lesson_no,
            'lesson_title' => $this->lesson_title,
            'objective' => $this->objective,
            'vocabulary' => $this->vocabulary,
            'grammar' => $this->grammar,
            'homework' => $this->homework,
            'duration' => $this->duration,
            'activities' => LessonPlanLessonActivityResource::collection($this->whenLoaded('activities')),
            'materials' => LessonPlanMaterialResource::collection($this->whenLoaded('materials')),
        ];
    }
}
