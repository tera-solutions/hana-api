<?php

namespace App\Modules\Education\Exam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamRegistrationResource extends JsonResource
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
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
