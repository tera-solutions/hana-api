<?php

namespace App\Modules\Education\Material\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialVersionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'material_id' => $this->material_id,
            'version' => $this->version,
            'file_id' => $this->file_id,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'change_log' => $this->change_log,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
