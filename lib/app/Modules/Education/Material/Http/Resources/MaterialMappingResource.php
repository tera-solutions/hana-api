<?php

namespace App\Modules\Education\Material\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialMappingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'material' => $this->whenLoaded('material', fn () => $this->material ? [
                'id' => $this->material->id,
                'name' => $this->material->name,
                'type' => $this->material->type,
            ] : null),
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'created_at' => $this->created_at,
        ];
    }
}
