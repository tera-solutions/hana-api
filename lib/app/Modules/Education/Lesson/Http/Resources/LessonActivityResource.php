<?php

namespace App\Modules\Education\Lesson\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LessonActivityResource extends JsonResource
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
