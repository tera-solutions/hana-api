<?php

namespace App\Modules\Education\ClassSessionFeedback\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassSessionFeedbackResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'session_id' => $this->session_id,
            'session' => $this->whenLoaded('session', fn () => $this->session ? [
                'id' => $this->session->id,
                'session_no' => $this->session->session_no,
                'name' => $this->session->name,
                'session_date' => $this->session->session_date,
            ] : null),

            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'code' => $this->student->code,
                'name' => $this->student->name,
            ] : null),

            'rating' => $this->rating,
            'comment' => $this->comment,

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
