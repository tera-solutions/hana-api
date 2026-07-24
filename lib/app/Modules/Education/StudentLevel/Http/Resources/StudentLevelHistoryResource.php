<?php

namespace App\Modules\Education\StudentLevel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentLevelHistoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'student_level_id' => $this->student_level_id,
            'action' => $this->action,
            'from_level_id' => $this->from_level_id,
            'from_level' => $this->whenLoaded('fromLevel', fn () => $this->fromLevel ? [
                'id' => $this->fromLevel->id,
                'level_name' => $this->fromLevel->level_name,
            ] : null),
            'to_level_id' => $this->to_level_id,
            'to_level' => $this->whenLoaded('toLevel', fn () => $this->toLevel ? [
                'id' => $this->toLevel->id,
                'level_name' => $this->toLevel->level_name,
            ] : null),
            'reason' => $this->reason,
            'reason_type' => $this->reason_type,
            'exam_result_id' => $this->exam_result_id,
            'score' => $this->score,
            'created_by' => $this->created_by,
            'effective_at' => $this->effective_at,
            'created_at' => $this->created_at,
        ];
    }
}
