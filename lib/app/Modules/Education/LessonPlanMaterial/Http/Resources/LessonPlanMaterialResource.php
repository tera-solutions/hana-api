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
            'file_id' => $this->file_id,
            'material_type' => $this->material_type,
        ];
    }
}
