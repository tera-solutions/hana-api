<?php

namespace App\Modules\Education\Course\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'thumbnail' => $this->thumbnail,
            'duration_minutes' => $this->duration_minutes,
            'price_per_lesson' => $this->price_per_lesson,
            'description' => $this->description,
            'is_active' => $this->is_active,

            'business_id' => $this->business_id,
            'business' => $this->whenLoaded('business', fn () => [
                'id' => $this->business?->id,
                'name' => $this->business?->name,
            ]),

            'curriculums' => $this->whenLoaded('curriculums', fn () => $this->curriculums->map(fn ($c) => [
                'id' => $c->id,
                'order' => $c->order,
                'title' => $c->title,
                'content' => $c->content,
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
