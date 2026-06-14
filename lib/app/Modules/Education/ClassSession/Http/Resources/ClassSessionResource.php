<?php

namespace App\Modules\Education\ClassSession\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassSessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'schedule_id' => $this->schedule_id,
            'session_no' => $this->session_no,
            'code' => $this->code,
            'name' => $this->name,

            'session_date' => $this->session_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,

            'room_id' => $this->room_id,

            'teacher_id' => $this->teacher_id,
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'full_name' => $this->teacher->full_name,
                'avatar' => $this->teacher->avatar,
            ] : null),

            'substitute_teacher_id' => $this->substitute_teacher_id,
            'substitute_teacher' => $this->whenLoaded('substituteTeacher', fn () => $this->substituteTeacher ? [
                'id' => $this->substituteTeacher->id,
                'full_name' => $this->substituteTeacher->full_name,
                'avatar' => $this->substituteTeacher->avatar,
            ] : null),

            'status' => $this->status,
            'attendance_locked' => $this->attendance_locked,
            'revenue_amount' => $this->revenue_amount,
            'note' => $this->note,

            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
