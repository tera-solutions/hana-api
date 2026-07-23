<?php

namespace App\Modules\Education\PlacementTest\Services;

use App\Modules\Education\PlacementTest\Models\PlacementTest;
use App\Modules\Education\PlacementTest\Models\PlacementTestResult;
use App\Modules\Education\Question\Models\Question;
use App\Modules\HR\Teacher\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class PlacementTestService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = PlacementTest::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('test_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'cefr_level', 'teacher_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['title', 'cefr_level', 'status', 'created_at']);

        $paginated = $query->withCount(['results', 'questions'])->paginate($this->resolvePerPage($params));
        $paginated->getCollection()->transform(fn (PlacementTest $test) => $this->withStats($test));

        return $paginated;
    }

    public function find($id): PlacementTest
    {
        return $this->withStats(
            PlacementTest::withCount('results')
                ->with(['questions.answers'])
                ->findOrFail($id)
        );
    }

    private function withStats(PlacementTest $test): PlacementTest
    {
        $results = PlacementTestResult::where('placement_test_id', $test->id)
            ->where('status', PlacementTestResult::STATUS_COMPLETED)
            ->get(['score']);

        $test->stats = [
            'attempts' => $test->results_count ?? $results->count(),
            'avg_score' => $results->count() > 0 ? round((float) $results->avg('score'), 1) : null,
            'completion_rate' => $test->results_count > 0
                ? round($results->count() / $test->results_count * 100)
                : 0,
        ];

        return $test;
    }

    public function create(array $data): PlacementTest
    {
        return DB::transaction(function () use ($data) {
            $test = new PlacementTest($data);
            $test->test_code = $this->generateCode();
            $test->status = PlacementTest::STATUS_DRAFT;
            $test->teacher_id = $data['teacher_id'] ?? $this->actingTeacherId();
            $test->save();

            return $this->find($test->id);
        });
    }

    public function update($id, array $data): PlacementTest
    {
        $test = PlacementTest::findOrFail($id);

        unset($data['id'], $data['test_code'], $data['status'], $data['teacher_id']);

        $test->update($data);

        return $this->find($test->id);
    }

    public function publish($id): PlacementTest
    {
        $test = PlacementTest::findOrFail($id);
        $test->update(['status' => PlacementTest::STATUS_PUBLISHED]);

        return $this->find($test->id);
    }

    public function delete($id): void
    {
        PlacementTest::findOrFail($id)->delete();
    }

    public function results($testId, array $params = [])
    {
        PlacementTest::findOrFail($testId);

        return PlacementTestResult::where('placement_test_id', $testId)
            ->with('student')
            ->orderByDesc('created_at')
            ->paginate($this->resolvePerPage($params));
    }

    /**
     * Draw ACTIVE bank questions into this test's question set, appending to
     * (not replacing) whatever is already attached — mirrors
     * `GenerateExamService::selectQuestions`, but links live bank rows
     * instead of snapshotting content (see `PlacementTest::questions()`).
     *
     * @param  array<int, array{skill: string, difficulty: string, count: int}>  $buckets
     *
     * @throws \RuntimeException
     */
    public function generateQuestions($testId, array $buckets): PlacementTest
    {
        return DB::transaction(function () use ($testId, $buckets) {
            $test = PlacementTest::findOrFail($testId);

            $selected = collect();
            foreach ($buckets as $bucket) {
                $rows = Question::query()
                    ->where('status', Question::STATUS_ACTIVE)
                    ->where('skill', $bucket['skill'])
                    ->where('difficulty', $bucket['difficulty'])
                    ->when($test->cefr_level, fn ($q) => $q->where('cefr_level', $test->cefr_level))
                    ->whereNotIn('id', $test->questions()->pluck('edu_questions.id'))
                    ->leftJoin('edu_question_statistics', 'edu_question_statistics.question_id', '=', 'edu_questions.id')
                    ->orderByRaw('COALESCE(edu_question_statistics.usage_count, 0) asc')
                    ->orderBy('edu_questions.id')
                    ->limit((int) $bucket['count'])
                    ->select('edu_questions.*')
                    ->get();

                $selected = $selected->concat($rows);
            }

            if ($selected->isEmpty()) {
                throw new \RuntimeException('Không tìm thấy câu hỏi phù hợp để thêm vào bài kiểm tra.');
            }

            $nextOrder = (int) $test->questions()->max('edu_placement_test_question.order');
            $attach = [];
            foreach ($selected as $i => $question) {
                $attach[$question->id] = ['order' => $nextOrder + $i + 1];
            }
            $test->questions()->syncWithoutDetaching($attach);

            $test->update(['question_count' => $test->questions()->count()]);

            return $this->find($testId);
        });
    }

    public function recordResult($testId, array $data): PlacementTestResult
    {
        PlacementTest::findOrFail($testId);

        return PlacementTestResult::updateOrCreate(
            ['placement_test_id' => $testId, 'student_id' => $data['student_id']],
            [
                'score' => $data['score'] ?? null,
                'cefr_result' => $data['cefr_result'] ?? null,
                'completion_rate' => $data['completion_rate'] ?? 100,
                'status' => $data['status'] ?? PlacementTestResult::STATUS_COMPLETED,
                'completed_at' => now(),
            ],
        );
    }

    private function generateCode(): string
    {
        $count = PlacementTest::count() + 1;

        return 'PLT'.str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }

    private function actingTeacherId(): ?int
    {
        $user = Auth::guard('api')->user() ?? Auth::user();

        return Teacher::where('user_id', $user?->id)->value('id');
    }
}
