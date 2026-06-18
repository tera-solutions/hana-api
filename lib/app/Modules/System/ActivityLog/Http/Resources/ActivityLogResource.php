<?php

namespace App\Modules\System\ActivityLog\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'entity' => $this->entity,
            'entity_id' => $this->entity_id,
            'action' => $this->action,
            'status' => $this->status,
            'description' => $this->description,

            'user_id' => $this->user_id,
            'role_id' => $this->role_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
            ]),

            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'request_id' => $this->request_id,
            'endpoint' => $this->endpoint,
            'method' => $this->method,

            'old_data' => $this->old_data,
            'new_data' => $this->new_data,
            'changed_fields' => $this->changed_fields,

            'created_at' => $this->created_at,
        ];
    }
}
