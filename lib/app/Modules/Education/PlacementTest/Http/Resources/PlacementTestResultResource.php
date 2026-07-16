<?php

namespace App\Modules\Education\PlacementTest\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlacementTestResultResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'placement_test_id' => $this->placement_test_id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'name' => $this->student->name,
            ] : null),
            'score' => $this->score,
            'cefr_result' => $this->cefr_result,
            'completion_rate' => $this->completion_rate,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
