<?php

namespace App\Modules\Education\LessonPlanVersion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonPlanVersionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'lesson_plan_id' => $this->lesson_plan_id,
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? [
                'id' => $this->plan->id,
                'plan_code' => $this->plan->plan_code,
                'plan_name' => $this->plan->plan_name,
            ] : null),
            'version' => $this->version,
            'change_summary' => $this->change_summary,
            'published_at' => $this->published_at,
            'published_by' => $this->published_by,
        ];
    }
}
