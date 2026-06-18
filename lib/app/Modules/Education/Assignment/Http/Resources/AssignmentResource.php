<?php

namespace App\Modules\Education\Assignment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'assignment_code' => $this->assignment_code,
            'assignment_name' => $this->assignment_name,
            'assignment_type' => $this->assignment_type,

            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
            ] : null),

            'level_id' => $this->level_id,

            'lesson_id' => $this->lesson_id,
            'lesson' => $this->whenLoaded('lesson', fn () => $this->lesson ? [
                'id' => $this->lesson->id,
                'lesson_no' => $this->lesson->lesson_no,
                'lesson_title' => $this->lesson->lesson_title,
            ] : null),

            'class_room_id' => $this->class_room_id,
            'class' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
                'code' => $this->classRoom->code,
                'name' => $this->classRoom->name,
            ] : null),

            'description' => $this->description,
            'instruction' => $this->instruction,
            'max_score' => $this->max_score,
            'due_date' => $this->due_date,
            'allow_late_submission' => $this->allow_late_submission,
            'allow_multiple_submission' => $this->allow_multiple_submission,
            'status' => $this->status,

            'submissions_count' => $this->whenCounted('submissions'),
            'submissions' => AssignmentSubmissionResource::collection($this->whenLoaded('submissions')),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
