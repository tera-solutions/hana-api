<?php

namespace App\Modules\Education\Enrollment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,

            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'code' => $this->student->code,
                'name' => $this->student->name,
                'phone' => $this->student->phone,
            ] : null),

            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
            ] : null),

            'class_id' => $this->class_id,
            'class' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
                'code' => $this->classRoom->code,
                'name' => $this->classRoom->name,
                'teacher' => $this->classRoom->relationLoaded('teacher') && $this->classRoom->teacher ? [
                    'id' => $this->classRoom->teacher->id,
                    'full_name' => $this->classRoom->teacher->full_name,
                ] : null,
            ] : null),

            'sales_id' => $this->sales_id,
            'sales' => $this->whenLoaded('sales', fn () => $this->sales ? [
                'id' => $this->sales->id,
                'name' => $this->sales->name,
            ] : null),

            'enrolled_at' => $this->enrolled_at?->toDateString(),

            'total_lessons' => (int) $this->total_lessons,
            'completed_lessons' => (int) $this->completed_lessons,
            'remaining_lessons' => (int) $this->remaining_lessons,

            'price_per_lesson' => (float) $this->price_per_lesson,
            'tuition_amount' => (float) $this->tuition_amount,
            'discount_amount' => (float) $this->discount_amount,
            'paid_amount' => (float) $this->paid_amount,
            'debt_amount' => (float) $this->debt_amount,

            'note' => $this->note,

            'business_id' => $this->business_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
