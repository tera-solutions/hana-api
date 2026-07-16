<?php

namespace App\Modules\Education\PlacementTest\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlacementTestResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'test_code' => $this->test_code,
            'title' => $this->title,
            'description' => $this->description,
            'cefr_level' => $this->cefr_level,
            'skills' => $this->skills,
            'question_count' => $this->question_count,
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'teacher_id' => $this->teacher_id,

            'results_count' => $this->whenCounted('results'),
            'stats' => $this->when(isset($this->stats), fn () => $this->stats),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
