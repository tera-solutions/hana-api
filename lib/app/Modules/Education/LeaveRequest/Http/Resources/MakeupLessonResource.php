<?php

namespace App\Modules\Education\LeaveRequest\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MakeupLessonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'leave_request_id' => $this->leave_request_id,
            'student_id' => $this->student_id,
            'original_lesson_id' => $this->original_lesson_id,
            'makeup_lesson_id' => $this->makeup_lesson_id,
            'status' => $this->status,
            'original_lesson' => $this->whenLoaded('originalLesson', fn () => $this->originalLesson ? [
                'id' => $this->originalLesson->id,
                'lesson_no' => $this->originalLesson->lesson_no,
                'lesson_date' => $this->originalLesson->lesson_date,
            ] : null),
            'makeup_lesson' => $this->whenLoaded('makeupLesson', fn () => $this->makeupLesson ? [
                'id' => $this->makeupLesson->id,
                'lesson_no' => $this->makeupLesson->lesson_no,
                'lesson_date' => $this->makeupLesson->lesson_date,
            ] : null),
            'created_at' => $this->created_at,
        ];
    }
}
