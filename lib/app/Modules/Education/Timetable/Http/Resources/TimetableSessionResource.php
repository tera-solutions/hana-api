<?php

namespace App\Modules\Education\Timetable\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A generated class session, as shown in calendar / schedule views.
 */
class TimetableSessionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'session_no' => $this->session_no,
            'name' => $this->name,
            'session_date' => $this->session_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,

            'class_id' => $this->class_id,
            'class' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
                'code' => $this->classRoom->code,
                'name' => $this->classRoom->name,
            ] : null),

            'teacher_id' => $this->teacher_id,
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'code' => $this->teacher->code,
                'full_name' => $this->teacher->full_name,
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
        ];
    }
}
