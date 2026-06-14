<?php

namespace App\Modules\Education\ClassRoom\Http\Resources;

use App\Modules\Education\ClassSchedule\Http\Resources\ClassScheduleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,

            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course?->id,
                'code' => $this->course?->code,
                'name' => $this->course?->name,
            ]),

            'teacher_id' => $this->teacher_id,
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'full_name' => $this->teacher->full_name,
                'avatar' => $this->teacher->avatar,
            ] : null),

            'assignee_id' => $this->assignee_id,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null),

            'room_id' => $this->room_id,

            'learning_type' => $this->learning_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,

            'min_warning_capacity' => $this->min_warning_capacity,
            'min_capacity' => $this->min_capacity,
            'max_warning_capacity' => $this->max_warning_capacity,
            'max_capacity' => $this->max_capacity,
            'capacity_warning' => $this->capacityWarning(),

            'use_course_curriculum' => $this->use_course_curriculum,
            'description' => $this->description,

            'schedules' => $this->whenLoaded(
                'schedules',
                fn () => ClassScheduleResource::collection($this->schedules)
            ),

            'business_id' => $this->business_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    /**
     * Badge per spec §8: thiếu học viên / sắp đạt tối đa / đã đầy.
     * Current student count comes from the pre-loaded `current_students` attribute
     * or defaults to 0 when enrollment data is not loaded.
     */
    private function capacityWarning(): ?string
    {
        $current = $this->current_students ?? 0;

        if ($this->max_capacity !== null && $current >= $this->max_capacity) {
            return 'full';
        }

        if ($this->max_warning_capacity !== null && $current >= $this->max_warning_capacity) {
            return 'almost_full';
        }

        if ($this->min_warning_capacity !== null && $current <= $this->min_warning_capacity) {
            return 'low';
        }

        return null;
    }
}
