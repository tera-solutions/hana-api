<?php

namespace App\Modules\Education\Exam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'exam_code' => $this->exam_code,
            'exam_name' => $this->exam_name,
            'exam_type' => $this->exam_type,

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
            ] : null),

            'duration' => $this->duration,
            'total_score' => $this->total_score,
            'passing_score' => $this->passing_score,
            'version' => $this->version,
            'root_exam_id' => $this->root_exam_id,
            'status' => $this->status,

            'questions_count' => $this->whenCounted('questions'),
            'questions' => ExamQuestionResource::collection($this->whenLoaded('questions')),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
