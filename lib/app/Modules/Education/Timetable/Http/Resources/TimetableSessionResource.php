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
            'timetable_id' => $this->timetable_id,
            'class_id' => $this->class_id,
            'session_no' => $this->session_no,
            'name' => $this->name,
            'session_date' => $this->session_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'teacher_id' => $this->teacher_id,
            'room_id' => $this->room_id,
            'status' => $this->status,
        ];
    }
}
