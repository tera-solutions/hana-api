<?php

namespace App\Modules\Education\LessonPlan\Services;

use App\Modules\Education\ClassRoom\Models\ClassRoom;
use App\Modules\Education\LessonPlan\Enums\LessonPlanStatus;
use App\Modules\Education\LessonPlan\Models\LessonPlan;
use App\Modules\Education\LessonPlanVersion\Services\LessonPlanVersionService;
use App\Modules\Education\Support\SummarizesByStatus;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class LessonPlanService
{
    use HandlesEntityQueries;
    use SummarizesByStatus;

    /**
     * Paginated, searchable, filterable, sortable list (lesson-plan.md §4).
     */
    public function paginate(array $params = [])
    {
        $query = $this->baseQuery($params);

        $this->applySort($query, $params, ['plan_code', 'plan_name', 'version', 'total_lessons', 'status', 'created_at']);

        return $query->with('course')->withCount('lessons')->paginate($this->resolvePerPage($params));
    }

    /**
     * Aggregate counters for the list view, honouring the same filters/scope as
     * {@see paginate()}.
     *
     * @return array{total: int, by_status: array<string, int>, total_lessons: int, in_use: int}
     */
    public function summary(array $params = []): array
    {
        $byStatus = (clone $this->baseQuery($params))
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as aggregate')
            ->pluck('aggregate', 'status');

        $planIds = (clone $this->baseQuery($params))->pluck('id');

        $inUse = ClassRoom::whereIn('lesson_plan_id', $planIds)
            ->distinct()
            ->count('lesson_plan_id');

        return [
            'total' => $this->baseQuery($params)->count(),
            'by_status' => $this->countsByStatus($byStatus, LessonPlanStatus::cases()),
            'total_lessons' => (int) (clone $this->baseQuery($params))->sum('total_lessons'),
            'in_use' => $inUse,
        ];
    }

    /**
     * The filtered, teacher-scoped base query shared by list and summary — no
     * sort, eager-loads or pagination applied.
     */
    private function baseQuery(array $params): Builder
    {
        $query = LessonPlan::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('plan_code', 'like', "%{$search}%")
                    ->orWhere('plan_name', 'like', "%{$search}%");
            });
        }

        if (! empty($params['course_id'])) {
            $query->where('course_id', $params['course_id']);
        }
        if (! empty($params['level_id'])) {
            $query->where('level_id', $params['level_id']);
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if ($scope = TeacherScope::current()) {
            $scope->constrainLessonPlans($query);
        }

        return $query;
    }

    public function find($id): LessonPlan
    {
        return LessonPlan::with('course')->withCount('lessons')->findOrFail($id);
    }

    /**
     * Detail with lessons (+materials), versions and usage (lesson-plan.md §4, §13).
     */
    public function detail($id): array
    {
        $plan = LessonPlan::with(['course', 'level', 'lessons.materials', 'versions'])->findOrFail($id);

        return [
            'plan' => $plan,
            'usage' => [
                'classes' => $this->countLinked('edu_classes', $id, 'lesson_plan_id'),
            ],
        ];
    }

    public function create(array $data): LessonPlan
    {
        return DB::transaction(function () use ($data) {
            $plan = new LessonPlan($data);
            $plan->version = 1;
            $plan->status = LessonPlan::STATUS_DRAFT;
            $plan->total_lessons = 0;
            $plan->save();

            return $this->find($plan->id);
        });
    }

    /**
     * Update plan metadata, including course_id and status. Blocked once published
     * or used by a class (§9, BR004). Setting status keeps published_at/published_by
     * coherent; it does not run the publish lesson checks (BR005/BR006) — use the
     * publish endpoint for that.
     *
     * @throws \RuntimeException
     */
    public function update($id, array $data): LessonPlan
    {
        return DB::transaction(function () use ($id, $data) {
            $plan = LessonPlan::findOrFail($id);

            $this->assertEditable($plan);

            unset($data['id'], $data['version'], $data['total_lessons'], $data['published_at'], $data['published_by']);

            if (array_key_exists('status', $data)) {
                if ($data['status'] === LessonPlan::STATUS_PUBLISHED) {
                    $data['published_at'] = now();
                    $data['published_by'] = Auth::guard('api')->id() ?? Auth::id();
                } else {
                    $data['published_at'] = null;
                    $data['published_by'] = null;
                }
            }

            $plan->update($data);

            return $this->find($plan->id);
        });
    }

    /**
     * Clone a plan into a new draft, deep-copying lessons and materials (§10).
     */
    public function clone($id, array $data): LessonPlan
    {
        return DB::transaction(function () use ($id, $data) {
            $source = LessonPlan::with('lessons.materials')->findOrFail($id);

            $clone = $source->replicate(['published_at', 'published_by']);
            $clone->plan_code = $data['plan_code'];
            $clone->plan_name = $data['plan_name'] ?? $source->plan_name;
            $clone->version = $source->version + 1;
            $clone->status = LessonPlan::STATUS_DRAFT;
            $clone->published_at = null;
            $clone->published_by = null;
            $clone->save();

            foreach ($source->lessons as $lesson) {
                $newLesson = $lesson->replicate();
                $newLesson->lesson_plan_id = $clone->id;
                $newLesson->save();

                foreach ($lesson->materials as $material) {
                    $newMaterial = $material->replicate();
                    $newMaterial->lesson_plan_lesson_id = $newLesson->id;
                    $newMaterial->save();
                }
            }

            $clone->update(['total_lessons' => $source->lessons->count()]);

            return $this->find($clone->id);
        });
    }

    /**
     * Publish a draft (§11). Requires at least one lesson (BR005), all valid (BR006).
     *
     * @throws \RuntimeException
     */
    public function publish($id, array $data): LessonPlan
    {
        return DB::transaction(function () use ($id, $data) {
            $plan = LessonPlan::with('lessons')->findOrFail($id);

            if ($plan->status === LessonPlan::STATUS_PUBLISHED) {
                throw new \RuntimeException('Giáo án đã được xuất bản.');
            }

            if ($plan->lessons->isEmpty()) {
                throw new \RuntimeException('Giáo án phải có ít nhất 1 buổi học trước khi xuất bản.');
            }

            $this->assertLessonsValid($plan);

            $userId = Auth::guard('api')->id() ?? Auth::id();

            $plan->update([
                'status' => LessonPlan::STATUS_PUBLISHED,
                'published_at' => now(),
                'published_by' => $userId,
            ]);

            app(LessonPlanVersionService::class)->record(
                $plan->id,
                $plan->version,
                $data['change_summary'] ?? null,
                $userId,
            );

            return $this->find($plan->id);
        });
    }

    /**
     * Archive a plan — stop using it (§5 ARCHIVED).
     *
     * @throws \RuntimeException
     */
    public function archive($id): LessonPlan
    {
        $plan = LessonPlan::findOrFail($id);

        if ($plan->status === LessonPlan::STATUS_ARCHIVED) {
            throw new \RuntimeException('Giáo án đã ngừng sử dụng.');
        }

        $plan->update(['status' => LessonPlan::STATUS_ARCHIVED]);

        return $this->find($plan->id);
    }

    /**
     * Restore an archived plan back to an editable draft. Re-publishing goes
     * through the normal publish flow (§5, §11).
     *
     * @throws \RuntimeException
     */
    public function restore($id): LessonPlan
    {
        $plan = LessonPlan::findOrFail($id);

        if ($plan->status !== LessonPlan::STATUS_ARCHIVED) {
            throw new \RuntimeException('Chỉ có thể khôi phục giáo án đã ngừng sử dụng.');
        }

        $plan->update(['status' => LessonPlan::STATUS_DRAFT]);

        return $this->find($plan->id);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Editability gate (§9, BR004). Public so lesson-template edits can reuse it.
     *
     * @throws \RuntimeException
     */
    public function assertEditable(LessonPlan $plan): void
    {
        if ($plan->status === LessonPlan::STATUS_PUBLISHED) {
            throw new \RuntimeException('Giáo án đã xuất bản không thể sửa trực tiếp. Hãy tạo phiên bản mới (clone).');
        }

        // BR004: a plan already used by a class must be versioned, not edited.
        if ($this->countLinked('edu_classes', $plan->id, 'lesson_plan_id') > 0) {
            throw new \RuntimeException('Giáo án đã được sử dụng bởi lớp học. Hãy tạo phiên bản mới (clone).');
        }
    }

    /**
     * @throws \RuntimeException
     */
    private function assertLessonsValid(LessonPlan $plan): void
    {
        $nos = $plan->lessons->pluck('lesson_no')->sort()->values();

        // BR006 + BR003: every lesson titled and the order is contiguous 1..N.
        foreach ($plan->lessons as $lesson) {
            if (blank($lesson->lesson_title)) {
                throw new \RuntimeException('Tất cả buổi học phải có tiêu đề trước khi xuất bản.');
            }
        }

        $expected = range(1, $nos->count());
        if ($nos->all() !== $expected) {
            throw new \RuntimeException('Thứ tự buổi học không liên tục.');
        }
    }
}
