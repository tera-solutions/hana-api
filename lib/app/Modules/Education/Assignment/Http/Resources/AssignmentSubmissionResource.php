<?php

namespace App\Modules\Education\Assignment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentSubmissionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'assignment' => $this->whenLoaded('assignment', fn () => $this->assignment ? [
                'id' => $this->assignment->id,
                'assignment_code' => $this->assignment->assignment_code,
                'assignment_name' => $this->assignment->assignment_name,
                'max_score' => $this->assignment->max_score,
            ] : null),
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'code' => $this->student->code,
                'name' => $this->student->name,
            ] : null),

            'status' => $this->status,
            'submitted_at' => $this->submitted_at,
            'answer' => $this->answer,
            'score' => $this->score,
            'comment' => $this->comment,
            'result_published' => $this->result_published,

            'files' => AssignmentSubmissionFileResource::collection($this->whenLoaded('files')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
