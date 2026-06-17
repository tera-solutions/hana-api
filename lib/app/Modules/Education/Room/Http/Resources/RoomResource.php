<?php

namespace App\Modules\Education\Room\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'room_code' => $this->room_code,
            'room_name' => $this->room_name,
            'floor' => $this->floor,
            'capacity' => $this->capacity,
            'room_type' => $this->room_type,
            'status' => $this->status,
            'description' => $this->description,

            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
                'code' => $this->branch?->code,
            ]),

            'active_classes_count' => $this->when(isset($this->active_classes_count), fn () => (int) $this->active_classes_count),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
