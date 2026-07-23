<?php

namespace App\Modules\Education\ClassRoom\Http\Resources;

use App\Modules\Education\Timetable\Models\Timetable;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,

            'course_id' => $this->course_id,
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course?->id,
                'code' => $this->course?->code,
                'name' => $this->course?->name,
            ]),

            'lesson_plan_id' => $this->lesson_plan_id,
            'lesson_plan' => $this->whenLoaded('lessonPlan', fn () => $this->lessonPlan ? [
                'id' => $this->lessonPlan->id,
                'plan_code' => $this->lessonPlan->plan_code,
                'plan_name' => $this->lessonPlan->plan_name,
                'version' => $this->lessonPlan->version,
                'status' => $this->lessonPlan->status,
            ] : null),

            // Plans available to pick from when starting a session (see
            // StartSessionRequest) — may hold more than one.
            'lesson_plans' => $this->whenLoaded('lessonPlans', fn () => $this->lessonPlans->map(fn ($plan) => [
                'id' => $plan->id,
                'plan_code' => $plan->plan_code,
                'plan_name' => $plan->plan_name,
                'status' => $plan->status,
            ])),

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

            // A class's own `room_id`/`end_date` are set when known upfront, but in
            // the normal flow (spec: class created first, schedule added afterwards
            // as a Timetable) they're left blank on the class and only really exist
            // on its current Timetable — fall back to that so the detail page isn't
            // permanently empty just because the class record itself was never
            // updated (same reasoning as `schedules` below).
            'room_id' => $this->room_id ?? $this->currentTimetable()?->room_id,
            // Not `whenLoaded('room', ...)`: that helper returns null without even
            // calling the closure whenever the class's OWN `room` relation value is
            // null — which is exactly the case this fallback needs to handle.
            'room' => $this->relationLoaded('room') ? $this->roomPayload() : null,

            'learning_type' => $this->learning_type,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => ($this->end_date ?? $this->currentTimetable()?->end_date)?->toDateString(),
            'status' => $this->status,

            'min_warning_capacity' => $this->min_warning_capacity,
            'min_capacity' => $this->min_capacity,
            'max_warning_capacity' => $this->max_warning_capacity,
            'max_capacity' => $this->max_capacity,
            'capacity_warning' => $this->capacityWarning(),

            'total_students' => $this->whenCounted('enrollments'),
            'avg_attendance_rate' => $this->avg_attendance_rate,

            'use_course_curriculum' => $this->use_course_curriculum,
            'description' => $this->description,

            // Kept as `schedules` for API/FE backward compatibility — sourced from the
            // class's current (most recent, non-cancelled) Timetable's rules instead of
            // the retired ClassSchedule table (timetable-management.md).
            'schedules' => $this->whenLoaded('timetables', fn () => $this->currentScheduleRules()),

            'business_id' => $this->business_id,
            'business' => $this->whenLoaded('business', fn () => $this->business ? [
                'id' => $this->business->id,
                'name' => $this->business->name,
                'email' => $this->business->email,
            ] : null),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }

    /**
     * The class's current timetable — its most recent non-cancelled one, if any.
     */
    private function currentTimetable(): ?Timetable
    {
        if (! $this->relationLoaded('timetables')) {
            return null;
        }

        return $this->timetables
            ->where('status', '!=', Timetable::STATUS_CANCELLED)
            ->first();
    }

    /**
     * The class's own room, falling back to its current timetable's room.
     */
    private function roomPayload(): ?array
    {
        $room = $this->room ?? $this->currentTimetable()?->room;

        return $room ? [
            'id' => $room->id,
            'room_code' => $room->room_code,
            'room_name' => $room->room_name,
        ] : null;
    }

    /**
     * The rules of the class's current timetable, shaped like the old
     * ClassSchedule rows so existing FE consumers of `schedules` keep working
     * unchanged.
     */
    private function currentScheduleRules(): array
    {
        $timetable = $this->currentTimetable();

        if (! $timetable) {
            return [];
        }

        return $timetable->rules->map(fn ($rule) => [
            'id' => $rule->id,
            'weekday' => $rule->day_of_week,
            'start_time' => $rule->start_time,
            'end_time' => $rule->end_time,
        ])->values()->all();
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
