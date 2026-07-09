<?php

namespace App\Modules\System\Notification\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'object_id' => $this->object_id,
            'object_type' => $this->object_type,
            'class_id' => $this->class_id,
            'type' => $this->type,
            'is_view' => (bool) $this->is_view,
            'created_by' => $this->whenLoaded('created_by', fn () => $this->created_by ? [
                'id' => $this->created_by->id,
                'name' => $this->created_by->name ?? $this->created_by->full_name ?? null,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
