<?php

namespace App\Modules\Education\LessonPlanVersion\Services;

use App\Modules\Education\LessonPlan\Models\LessonPlan;
use App\Modules\Education\LessonPlanVersion\Models\LessonPlanVersion;
use Illuminate\Database\Eloquent\Collection;

class LessonPlanVersionService
{
    /**
     * Version history of a plan, newest first (lesson-plan.md §13).
     *
     * @return Collection<int, LessonPlanVersion>
     */
    public function listForPlan($planId): Collection
    {
        // LessonPlan::versions() already orders newest-first.
        return LessonPlan::findOrFail($planId)->versions;
    }

    public function find($id): LessonPlanVersion
    {
        return LessonPlanVersion::with('plan')->findOrFail($id);
    }

    /**
     * Append a version snapshot — called from the publish flow (§11, §13).
     */
    public function record($planId, int $version, ?string $changeSummary, $userId): LessonPlanVersion
    {
        return LessonPlanVersion::create([
            'lesson_plan_id' => $planId,
            'version' => $version,
            'change_summary' => $changeSummary,
            'published_at' => now(),
            'published_by' => $userId,
        ]);
    }
}
