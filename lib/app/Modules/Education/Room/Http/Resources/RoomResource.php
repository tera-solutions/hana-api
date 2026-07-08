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
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
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
            // First active class using the room (room.md list §5.2 "Lớp học đang sử dụng").
            'active_class' => $this->whenLoaded('classes', function () {
                $class = $this->classes->first();

                return $class ? [
                    'id' => $class->id,
                    'name' => $class->name,
                    'current_students' => (int) $class->enrollments_count,
                    'max_capacity' => $class->max_capacity,
                ] : null;
            }),
            // Nearest upcoming booking (room.md list §5.2 "Lịch học tiếp theo"); set by RoomService::attachNextSessions().
            'next_session' => $this->when(isset($this->next_session), fn () => $this->next_session),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
