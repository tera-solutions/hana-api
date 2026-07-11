<?php

namespace App\Modules\Education\LessonPlanLesson\Services;

use App\Modules\Education\LessonPlan\Services\LessonPlanService;
use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLesson;
use App\Modules\Education\LessonPlanLesson\Models\LessonPlanLessonActivity;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class LessonPlanLessonActivityService
{
    use HandlesEntityQueries;

    /**
     * Paginated list of a lesson-plan-lesson's activities.
     */
    public function paginate(array $params = [])
    {
        $query = LessonPlanLessonActivity::query();

        if (! empty($params['lesson_plan_lesson_id'])) {
            $query->where('lesson_plan_lesson_id', $params['lesson_plan_lesson_id']);
        }

        if (empty($params['sort_by'])) {
            $params['sort_by'] = 'sort_order';
            $params['sort_dir'] = 'asc';
        }
        $this->applySort($query, $params, ['sort_order', 'title', 'created_at'], 'sort_order');

        return $query->paginate($this->resolvePerPage($params));
    }

    public function find($id): LessonPlanLessonActivity
    {
        return LessonPlanLessonActivity::findOrFail($id);
    }

    /**
     * Append an activity to a lesson-plan-lesson (BR008 editability applies —
     * same rule that gates the lesson itself: draft plan, not linked to a class).
     *
     * @throws \RuntimeException
     */
    public function create(array $data): LessonPlanLessonActivity
    {
        return DB::transaction(function () use ($data) {
            $lesson = LessonPlanLesson::findOrFail($data['lesson_plan_lesson_id']);

            $this->assertEditable($lesson);

            $data['sort_order'] ??= $this->nextSortOrder($lesson->id);

            return LessonPlanLessonActivity::create($data);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function update($id, array $data): LessonPlanLessonActivity
    {
        return DB::transaction(function () use ($id, $data) {
            $activity = $this->find($id);

            $this->assertEditable($activity->lesson);

            unset($data['id'], $data['lesson_plan_lesson_id']);

            $activity->update($data);

            return $activity->fresh();
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function delete($id): void
    {
        DB::transaction(function () use ($id) {
            $activity = $this->find($id);

            $this->assertEditable($activity->lesson);

            $activity->delete();
        });
    }

    private function assertEditable(LessonPlanLesson $lesson): void
    {
        app(LessonPlanService::class)->assertEditable($lesson->plan);
    }

    private function nextSortOrder($lessonPlanLessonId): int
    {
        return (int) LessonPlanLessonActivity::where('lesson_plan_lesson_id', $lessonPlanLessonId)->max('sort_order') + 1;
    }
}
