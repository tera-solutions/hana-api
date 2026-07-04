<?php

namespace App\Modules\Education\Material\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaterialCategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'category_name' => $this->category_name,
            'category_code' => $this->category_code,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'materials_count' => $this->whenCounted('materials'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
