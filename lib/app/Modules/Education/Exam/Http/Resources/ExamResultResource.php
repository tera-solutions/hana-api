<?php

namespace App\Modules\Education\Exam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamResultResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'exam_session_id' => $this->exam_session_id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'code' => $this->student->code,
                'name' => $this->student->name,
            ] : null),

            'listening_score' => $this->listening_score,
            'speaking_score' => $this->speaking_score,
            'reading_score' => $this->reading_score,
            'writing_score' => $this->writing_score,
            'grammar_score' => $this->grammar_score,
            'vocabulary_score' => $this->vocabulary_score,

            'total_score' => $this->total_score,
            'grade' => $this->grade,
            'passed' => $this->passed,
            'published_at' => $this->published_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
