<?php

namespace App\Modules\Education\LessonPlanMaterial\Services;

use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLesson;
use App\Modules\Education\LessonPlanMaterial\Models\LessonPlanMaterial;

class LessonPlanMaterialService
{
    // ── Materials (lesson-plan.md §14) ──────────────────────────────────────────

    public function attachMaterial($lessonId, array $data): LessonPlanMaterial
    {
        $lesson = LessonPlanLesson::findOrFail($lessonId);

        return LessonPlanMaterial::create([
            'lesson_plan_lesson_id' => $lesson->id,
            'file_id' => $data['file_id'],
            'material_type' => $data['material_type'],
        ]);
    }

    public function detachMaterial($materialId): void
    {
        LessonPlanMaterial::findOrFail($materialId)->delete();
    }
}
