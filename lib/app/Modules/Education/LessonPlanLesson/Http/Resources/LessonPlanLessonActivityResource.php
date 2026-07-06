<?php

namespace App\Modules\Education\LessonPlanLesson\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonPlanLessonActivityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sort_order' => $this->sort_order,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'title' => $this->title,
            'description' => $this->description,
            'duration' => $this->duration,
            'status' => $this->status,
        ];
    }
}
