<?php

namespace App\Modules\HR\Timesheet\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetSessionResource extends JsonResource
{
    public function toArray($request)
    {
        $hours = ($this->start_time && $this->end_time)
            ? Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)) / 60
            : 0;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'session_date' => $this->session_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'hours' => round($hours, 2),
            'status' => $this->status,

            'class_id' => $this->class_id,
            'class_name' => $this->classRoom?->name,
            'learning_type' => $this->classRoom?->learning_type,

            'room_name' => $this->room?->name,

            'present_count' => (int) ($this->present_count ?? 0),
            'absent_count' => (int) ($this->absent_count ?? 0),
            'attendances_count' => (int) ($this->attendances_count ?? 0),
            'average_rating' => $this->feedbacks_avg_rating !== null ? round((float) $this->feedbacks_avg_rating, 2) : null,
        ];
    }
}
