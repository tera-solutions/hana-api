<?php

namespace App\Modules\HR\Achievement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TeacherReviewResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'avatar_url' => $this->student->avatar_url,
            ] : null),
            'class_id' => $this->class_id,
            'rating' => $this->rating,
            'content' => $this->content,
            'created_at' => $this->created_at,
        ];
    }
}
