<?php

namespace App\Modules\Education\ClassSchedule\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'class' => $this->whenLoaded('eduClass', fn () => $this->eduClass ? [
                'id' => $this->eduClass->id,
                'code' => $this->eduClass->code,
                'name' => $this->eduClass->name,
            ] : null),
            'weekday' => $this->weekday,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }
}
