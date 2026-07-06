<?php

namespace App\Modules\Education\LessonPlanLesson\Services;

use App\Modules\Education\LessonPlan\Models\LessonPlan;
use App\Modules\Education\LessonPlan\Services\LessonPlanService;
use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLesson;
use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLessonActivity;
use Illuminate\Support\Facades\DB;

class LessonPlanLessonService
{
    // ── Lesson templates (lesson-plan.md §8) ────────────────────────────────────

    /**
     * Append a lesson. Honours BR002 (unique no) and BR003 (continuous order).
     *
     * @throws \RuntimeException
     */
    public function addLesson($planId, array $data): LessonPlanLesson
    {
        return DB::transaction(function () use ($planId, $data) {
            $plan = LessonPlan::findOrFail($planId);

            $this->assertEditable($plan);

            $nextNo = $this->nextLessonNo($planId);

            // BR003: lessons must stay contiguous, so a new one can only append.
            if (isset($data['lesson_no']) && (int) $data['lesson_no'] !== $nextNo) {
                throw new \RuntimeException("Thứ tự buổi học phải liên tục. Buổi học tiếp theo phải là số {$nextNo}.");
            }

            $activities = $data['activities'] ?? null;
            unset($data['activities']);

            $data['lesson_no'] = $nextNo;
            $data['lesson_plan_id'] = $planId;

            $lesson = LessonPlanLesson::create($data);

            if ($activities !== null) {
                $this->syncActivities($lesson, $activities);
            }

            $this->recomputeTotals($planId);

            return $lesson->load('activities');
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function updateLesson($lessonId, array $data): LessonPlanLesson
    {
        return DB::transaction(function () use ($lessonId, $data) {
            $lesson = LessonPlanLesson::findOrFail($lessonId);

            $this->assertEditable($lesson->plan);

            $activities = $data['activities'] ?? null;
            unset($data['id'], $data['lesson_plan_id'], $data['lesson_no'], $data['activities']);

            $lesson->update($data);

            if ($activities !== null) {
                $this->syncActivities($lesson, $activities);
            }

            return $lesson->fresh()->load('activities');
        });
    }

    /**
     * Replace the activity set with the given ordered list (BR008).
     */
    private function syncActivities(LessonPlanLesson $lesson, array $activities): void
    {
        $lesson->activities()->forceDelete();

        foreach (array_values($activities) as $index => $activity) {
            $lesson->activities()->create([
                'sort_order' => $index + 1,
                'avatar' => $activity['avatar'] ?? null,
                'title' => $activity['title'],
                'description' => $activity['description'] ?? null,
                'duration' => $activity['duration'] ?? null,
                'status' => $activity['status'] ?? LessonPlanLessonActivity::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Delete a lesson and re-sequence the remainder to keep BR003 continuity.
     *
     * @throws \RuntimeException
     */
    public function deleteLesson($lessonId): void
    {
        DB::transaction(function () use ($lessonId) {
            $lesson = LessonPlanLesson::findOrFail($lessonId);
            $planId = $lesson->lesson_plan_id;

            $this->assertEditable($lesson->plan);

            // Hard delete so the freed lesson_no leaves the (plan, lesson_no) unique
            // index; generated sessions keep their snapshot (FK is nullOnDelete, BR007).
            $lesson->forceDelete();

            $this->resequence($planId);
            $this->recomputeTotals($planId);
        });
    }

    /**
     * Reorder lessons by an ordered list of ids; reassigns lesson_no 1..N (BR003).
     *
     * @throws \RuntimeException
     */
    public function reorderLessons($planId, array $orderedIds): LessonPlan
    {
        return DB::transaction(function () use ($planId, $orderedIds) {
            $plan = LessonPlan::findOrFail($planId);

            $this->assertEditable($plan);

            $existing = LessonPlanLesson::where('lesson_plan_id', $planId)->pluck('id')->sort()->values();

            if ($existing->diff($orderedIds)->isNotEmpty() || collect($orderedIds)->diff($existing)->isNotEmpty()) {
                throw new \RuntimeException('Danh sách sắp xếp phải chứa đúng tất cả buổi học của giáo án.');
            }

            $this->assignSequentialNos($orderedIds);

            return app(LessonPlanService::class)->find($planId);
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Lesson edits are gated by the parent plan's editability (§9, BR004).
     *
     * @throws \RuntimeException
     */
    private function assertEditable(LessonPlan $plan): void
    {
        app(LessonPlanService::class)->assertEditable($plan);
    }

    private function nextLessonNo($planId): int
    {
        return (int) LessonPlanLesson::where('lesson_plan_id', $planId)->max('lesson_no') + 1;
    }

    private function resequence($planId): void
    {
        $ids = LessonPlanLesson::where('lesson_plan_id', $planId)->orderBy('lesson_no')->pluck('id');

        $this->assignSequentialNos($ids);
    }

    /**
     * Assign lesson_no 1..N following the given id order. Two passes via negative
     * offsets avoid transient collisions with the unique (plan, lesson_no) index.
     *
     * @param  iterable<int, int>  $orderedIds
     */
    private function assignSequentialNos(iterable $orderedIds): void
    {
        foreach ($orderedIds as $i => $id) {
            LessonPlanLesson::where('id', $id)->update(['lesson_no' => -($i + 1)]);
        }
        foreach ($orderedIds as $i => $id) {
            LessonPlanLesson::where('id', $id)->update(['lesson_no' => $i + 1]);
        }
    }

    private function recomputeTotals($planId): void
    {
        LessonPlan::where('id', $planId)->update([
            'total_lessons' => LessonPlanLesson::where('lesson_plan_id', $planId)->count(),
        ]);
    }
}
