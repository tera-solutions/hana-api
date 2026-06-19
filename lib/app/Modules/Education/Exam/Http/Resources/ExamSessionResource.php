<?php

namespace App\Modules\Education\Exam\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExamSessionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'exam' => $this->whenLoaded('exam', fn () => $this->exam ? [
                'id' => $this->exam->id,
                'exam_code' => $this->exam->exam_code,
                'exam_name' => $this->exam->exam_name,
                'exam_type' => $this->exam->exam_type,
            ] : null),

            'class_room_id' => $this->class_room_id,
            'class' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
                'code' => $this->classRoom->code,
                'name' => $this->classRoom->name,
            ] : null),

            'room_id' => $this->room_id,
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'room_code' => $this->room->room_code,
                'room_name' => $this->room->room_name,
            ] : null),

            'teacher_id' => $this->teacher_id,
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'code' => $this->teacher->code,
                'full_name' => $this->teacher->full_name,
            ] : null),

            'exam_date' => $this->exam_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,

            'registrations_count' => $this->whenCounted('registrations'),
            'registrations' => ExamRegistrationResource::collection($this->whenLoaded('registrations')),
            'results' => ExamResultResource::collection($this->whenLoaded('results')),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
