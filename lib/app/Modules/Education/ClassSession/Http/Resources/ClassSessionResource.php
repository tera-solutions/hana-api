<?php

namespace App\Modules\Education\ClassSession\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClassSessionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'session_no' => $this->session_no,
            'code' => $this->code,
            'name' => $this->name,

            'session_date' => $this->session_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,

            'class_id' => $this->class_id,
            'class' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
                'code' => $this->classRoom->code,
                'name' => $this->classRoom->name,
            ] : null),

            'schedule_id' => $this->schedule_id,
            'schedule' => $this->whenLoaded('schedule', fn () => $this->schedule ? [
                'id' => $this->schedule->id,
                'weekday' => $this->schedule->weekday,
                'start_time' => $this->schedule->start_time,
                'end_time' => $this->schedule->end_time,
            ] : null),

            'room_id' => $this->room_id,
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'room_code' => $this->room->room_code,
                'room_name' => $this->room->room_name,
            ] : null),

            'timetable_id' => $this->timetable_id,
            'timetable' => $this->whenLoaded('timetable', fn () => $this->timetable ? [
                'id' => $this->timetable->id,
                'timetable_code' => $this->timetable->timetable_code,
                'name' => $this->timetable->name,
            ] : null),

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
