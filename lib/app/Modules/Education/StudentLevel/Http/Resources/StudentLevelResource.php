<?php

namespace App\Modules\Education\StudentLevel\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentLevelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'code' => $this->student->code,
                'name' => $this->student->name,
            ] : null),

            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
            ] : null),

            'level_id' => $this->level_id,
            'level' => $this->whenLoaded('level', fn () => $this->level ? [
                'id' => $this->level->id,
                'level_code' => $this->level->level_code,
                'level_name' => $this->level->level_name,
                'level_order' => $this->level->level_order,
                'cefr_level' => $this->level->cefr_level,
            ] : null),

            'assigned_at' => $this->assigned_at,
            'assigned_by' => $this->assigned_by,
            'placement_score' => $this->placement_score,
            'status' => $this->status,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
