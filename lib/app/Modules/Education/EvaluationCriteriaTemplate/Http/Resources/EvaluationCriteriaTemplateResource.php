<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationCriteriaTemplateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'evaluation_type' => $this->evaluation_type,
            'name' => $this->name,
            'criteria' => $this->criteria,
            'criteria_count' => count($this->criteria ?? []),
            'criteria_descriptions' => $this->criteria_descriptions,
            'is_shared' => (bool) $this->is_shared,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
