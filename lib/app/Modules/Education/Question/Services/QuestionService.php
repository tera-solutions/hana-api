<?php

namespace App\Modules\Education\Question\Services;

use App\Helpers\Task;
use App\Modules\Education\Question\Models\Question;
use App\Modules\Education\Question\Models\QuestionAnswer;
use App\Modules\Education\Question\Models\QuestionStatistic;
use App\Modules\Education\QuestionVersion\Services\QuestionVersionService;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class QuestionService
{
    use HandlesEntityQueries;

    /**
     * Valid review-workflow transitions (question.md §IX).
     *
     * @var array<string, string>
     */
    private const TRANSITIONS = [
        'review' => Question::STATUS_REVIEWING,
        'approve' => Question::STATUS_APPROVED,
        'activate' => Question::STATUS_ACTIVE,
        'archive' => Question::STATUS_ARCHIVED,
    ];

    /**
     * The status a question must hold before each transition.
     *
     * @var array<string, string>
     */
    private const TRANSITION_FROM = [
        'review' => Question::STATUS_DRAFT,
        'approve' => Question::STATUS_REVIEWING,
        'activate' => Question::STATUS_APPROVED,
    ];

    /**
     * Paginated, searchable, filterable question bank (question.md §XV).
     */
    public function paginate(array $params = [])
    {
        $query = Question::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('question_code', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        foreach (['question_type', 'skill', 'difficulty', 'level_id', 'category_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['question_code', 'skill', 'difficulty', 'status', 'created_at']);

        return $query->with(['category', 'level'])->withCount('answers')->paginate($this->resolvePerPage($params));
    }

    public function find($id): Question
    {
        return Question::with(['answers', 'tags', 'category', 'level', 'statistic'])->findOrFail($id);
    }

    /**
     * Create a draft question with its answers, tags and a fresh statistics row
     * (question.md §VIII).
     */
    public function create(array $data): Question
    {
        return DB::transaction(function () use ($data) {
            $answers = $data['answers'] ?? [];
            $tagIds = $data['tag_ids'] ?? [];
            unset($data['answers'], $data['tag_ids']);

            $question = new Question($data);
            $question->question_code = $this->generateCode();
            $question->version = 1;
            $question->status = Question::STATUS_DRAFT;
            $question->save();

            $this->syncAnswers($question, $answers);
            $question->tags()->sync($tagIds);

            QuestionStatistic::firstOrCreate(['question_id' => $question->id]);

            return $this->find($question->id);
        });
    }

    /**
     * Update a question. Editing one that is already in use (active or previously used)
     * snapshots the prior state into a new version and bumps the version counter
     * (question.md §IX BR006/BR007).
     */
    public function update($id, array $data): Question
    {
        return DB::transaction(function () use ($id, $data) {
            $question = Question::with('answers')->findOrFail($id);

            $answers = $data['answers'] ?? null;
            $tagIds = $data['tag_ids'] ?? null;
            unset($data['answers'], $data['tag_ids'], $data['id'], $data['question_code'], $data['version'], $data['status']);

            if ($this->isInUse($question)) {
                app(QuestionVersionService::class)->record($question, $data['change_log'] ?? 'Cập nhật câu hỏi đã sử dụng.');
                $data['version'] = $question->version + 1;
            }
            unset($data['change_log']);

            $question->update($data);

            if (is_array($answers)) {
                $this->syncAnswers($question, $answers);
            }
            if (is_array($tagIds)) {
                $question->tags()->sync($tagIds);
            }

            return $this->find($question->id);
        });
    }

    public function delete($id): void
    {
        Question::findOrFail($id)->delete();
    }

    /**
     * Clone a question into a fresh draft (question.md §IV "Clone câu hỏi").
     */
    public function clone($id): Question
    {
        return DB::transaction(function () use ($id) {
            $source = Question::with(['answers', 'tags'])->findOrFail($id);

            $copy = $source->replicate(['question_code', 'created_by', 'updated_by', 'deleted_by']);
            $copy->question_code = $this->generateCode();
            $copy->version = 1;
            $copy->status = Question::STATUS_DRAFT;
            $copy->save();

            foreach ($source->answers as $answer) {
                $clone = $answer->replicate();
                $clone->question_id = $copy->id;
                $clone->save();
            }

            $copy->tags()->sync($source->tags->pluck('id')->all());

            QuestionStatistic::firstOrCreate(['question_id' => $copy->id]);

            return $this->find($copy->id);
        });
    }

    /**
     * Advance a question through the review workflow (question.md §IX).
     *
     * @throws \RuntimeException
     */
    public function transition($id, string $action): Question
    {
        $question = Question::findOrFail($id);

        $target = self::TRANSITIONS[$action] ?? throw new \RuntimeException('Hành động không hợp lệ.');

        if (isset(self::TRANSITION_FROM[$action]) && $question->status !== self::TRANSITION_FROM[$action]) {
            throw new \RuntimeException('Trạng thái hiện tại không cho phép thực hiện hành động này.');
        }

        $question->update(['status' => $target]);

        return $this->find($question->id);
    }

    /**
     * A question is "in use" once it is active or has been drawn into an exam
     * (question.md §IX BR006).
     */
    private function isInUse(Question $question): bool
    {
        return $question->status === Question::STATUS_ACTIVE
            || (int) QuestionStatistic::where('question_id', $question->id)->value('usage_count') > 0;
    }

    /**
     * Replace a question's answers with the given set.
     *
     * @param  array<int, array<string, mixed>>  $answers
     */
    private function syncAnswers(Question $question, array $answers): void
    {
        $question->answers()->delete();

        foreach ($answers as $i => $answer) {
            QuestionAnswer::create([
                'question_id' => $question->id,
                'answer_key' => $answer['answer_key'] ?? null,
                'answer_content' => $answer['answer_content'],
                'is_correct' => (bool) ($answer['is_correct'] ?? false),
                'sort_order' => $answer['sort_order'] ?? $i,
            ]);
        }
    }

    private function generateCode(): string
    {
        $count = Task::setAndGetReferenceCount('question');

        return Task::generateReferenceNumber('question', $count, 'QST');
    }
}
