<?php

namespace App\Modules\Education\Lesson\Http\Resources;

use App\Modules\Education\Lesson\Models\Lesson;
use App\Modules\Education\LessonPlanMaterial\Http\Resources\LessonPlanMaterialResource;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'class_room_id' => $this->class_room_id,
            'lesson_plan_id' => $this->lesson_plan_id,
            'lesson_plan_lesson_id' => $this->lesson_plan_lesson_id,
            'lesson_no' => $this->lesson_no,
            'lesson_title' => $this->lesson_title,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar_url,
            'lesson_date' => $this->lesson_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room_id' => $this->room_id,
            'teacher_id' => $this->teacher_id,

            'objective' => $this->objective,
            'vocabulary' => $this->vocabulary,
            'grammar' => $this->grammar,
            'activities' => $this->activities,
            'homework' => $this->homework,

            'lesson_note' => $this->lesson_note,
            'status' => $this->status,
            'is_locked' => $this->status === Lesson::STATUS_LOCKED,
            'completed_at' => $this->completed_at,
            'locked_at' => $this->locked_at,

            'class' => $this->whenLoaded('classRoom', fn () => [
                'id' => $this->classRoom?->id,
                'name' => $this->classRoom?->name,
                'code' => $this->classRoom?->code,
            ]),
            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher?->id,
                'name' => $this->teacher?->full_name,
            ]),
            'room' => $this->whenLoaded('room', fn () => [
                'id' => $this->room?->id,
                'name' => $this->room?->room_name,
            ]),
            'histories' => LessonHistoryResource::collection($this->whenLoaded('histories')),
            'materials' => $this->whenLoaded('lessonPlanLesson', fn ($lessonPlanLesson) => LessonPlanMaterialResource::collection($lessonPlanLesson?->materials ?? collect())),

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
