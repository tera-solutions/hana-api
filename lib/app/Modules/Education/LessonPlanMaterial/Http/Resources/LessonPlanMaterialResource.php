<?php

namespace App\Modules\Education\LessonPlanMaterial\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonPlanMaterialResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'lesson_plan_lesson_id' => $this->lesson_plan_lesson_id,
            'lesson' => $this->whenLoaded('lesson', fn () => $this->lesson ? [
                'id' => $this->lesson->id,
                'lesson_no' => $this->lesson->lesson_no,
                'lesson_title' => $this->lesson->lesson_title,
            ] : null),
            'file_id' => $this->file_id,
            'material_type' => $this->material_type,
        ];
    }
}
