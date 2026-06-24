<?php

namespace App\Modules\Education\Timetable\Http\Resources;

use App\Modules\Education\Timetable\Enums\SchedulePattern;
use App\Modules\Education\Timetable\Enums\TimetableStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class TimetableResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'timetable_code' => $this->timetable_code,
            'name' => $this->name,

            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => $this->course ? [
                'id' => $this->course->id,
                'name' => $this->course->name,
            ] : null),

            'class_room_id' => $this->class_room_id,
            'class_room' => $this->whenLoaded('classRoom', fn () => $this->classRoom ? [
                'id' => $this->classRoom->id,
                'name' => $this->classRoom->name,
            ] : null),

            'teacher_id' => $this->teacher_id,
            'room_id' => $this->room_id,

            'start_date' => $this->start_date,
            'end_date' => $this->end_date,

            'schedule_pattern' => $this->schedule_pattern,
            'schedule_pattern_label' => SchedulePattern::tryFrom((string) $this->schedule_pattern)?->label(),
            'total_sessions' => $this->total_sessions,

            'status' => $this->status,
            'status_label' => TimetableStatus::tryFrom((string) $this->status)?->label(),

            'rules' => $this->whenLoaded('rules', fn () => $this->rules->map(fn ($rule) => [
                'id' => $rule->id,
                'day_of_week' => $rule->day_of_week,
                'start_time' => $rule->start_time,
                'end_time' => $rule->end_time,
            ])),

            'sessions' => TimetableSessionResource::collection($this->whenLoaded('sessions')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
