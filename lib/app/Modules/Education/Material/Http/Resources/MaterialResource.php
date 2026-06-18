<?php

namespace App\Modules\Education\Material\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'material_code' => $this->material_code,
            'material_name' => $this->material_name,
            'material_type' => $this->material_type,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'category_code' => $this->category->category_code,
                'category_name' => $this->category->category_name,
            ] : null),
            'description' => $this->description,
            'current_version' => $this->current_version,
            'access_type' => $this->access_type,
            'status' => $this->status,

            'current_file' => $this->whenLoaded('versions', function () {
                $current = $this->versions->firstWhere('version', $this->current_version);

                return $current ? [
                    'version' => $current->version,
                    'file_id' => $current->file_id,
                    'file_name' => $current->file_name,
                    'file_size' => $current->file_size,
                    'mime_type' => $current->mime_type,
                ] : null;
            }),

            'versions' => MaterialVersionResource::collection($this->whenLoaded('versions')),
            'mappings' => MaterialMappingResource::collection($this->whenLoaded('mappings')),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
