<?php

namespace App\Modules\Education\Question\Services;

use App\Modules\Education\Exam\Models\Exam;
use App\Modules\Education\Exam\Services\ExamService;
use App\Modules\Education\Question\Models\Question;
use App\Modules\Education\Question\Models\QuestionStatistic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Auto-generate an exam from the question bank (question.md §XI). Wires the Question
 * module to the Exam module: selects ACTIVE bank questions by skill/level/difficulty,
 * snapshots them into edu_exam_questions, records usage in the edu_exam_question pivot
 * and bumps each question's usage_count.
 */
class GenerateExamService
{
    public function __construct(private ExamService $examService) {}

    /**
     * @param  array<string, mixed>  $data  exam metadata + skill/level_id + difficulty buckets
     *
     * @throws \RuntimeException
     */
    public function generate(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $selected = $this->selectQuestions($data);

            if ($selected->isEmpty()) {
                throw new \RuntimeException('Không tìm thấy câu hỏi phù hợp để sinh đề thi.');
            }

            $questionRows = $selected->map(fn (Question $q) => [
                'skill' => $q->skill,
                'question_type' => $q->question_type,
                'content' => $q->content,
                'answer_key' => $this->answerKey($q),
                'score' => $q->score,
                'difficulty' => $q->difficulty,
            ])->all();

            $exam = $this->examService->createFromBank([
                'exam_name' => $data['exam_name'],
                'exam_type' => $data['exam_type'],
                'course_id' => $data['course_id'] ?? null,
                'level_id' => $data['level_id'] ?? null,
                'duration' => $data['duration'],
                'total_score' => $selected->sum(fn (Question $q) => (float) $q->score),
                'passing_score' => $data['passing_score'],
            ], $questionRows);

            $this->recordUsage($exam, $selected);

            return $exam;
        });
    }

    /**
     * Pick ACTIVE questions per difficulty bucket, least-used first (BR005, BR009, BR010).
     *
     * @return Collection<int, Question>
     */
    private function selectQuestions(array $data): Collection
    {
        $skill = $data['skill'];
        $levelId = $data['level_id'] ?? null;
        $buckets = $data['difficulties']; // [{difficulty, count}, ...]

        $selected = collect();

        foreach ($buckets as $bucket) {
            $rows = Question::query()
                ->where('status', Question::STATUS_ACTIVE) // BR005 + BR009 (archived excluded)
                ->where('skill', $skill)
                ->where('difficulty', $bucket['difficulty'])
                ->when($levelId, fn ($q) => $q->where('level_id', $levelId))
                ->leftJoin('edu_question_statistics', 'edu_question_statistics.question_id', '=', 'edu_questions.id')
                ->orderByRaw('COALESCE(edu_question_statistics.usage_count, 0) asc') // BR010
                ->orderBy('edu_questions.id')
                ->limit((int) $bucket['count'])
                ->select('edu_questions.*')
                ->with('answers')
                ->get();

            $selected = $selected->concat($rows);
        }

        return $selected;
    }

    /**
     * The correct answer keys for a question, used as the exam question's answer_key.
     *
     * @return array<int, string>
     */
    private function answerKey(Question $question): array
    {
        return $question->answers
            ->where('is_correct', true)
            ->map(fn ($a) => $a->answer_key ?? $a->answer_content)
            ->values()
            ->all();
    }

    /**
     * Record bank usage: the exam↔question pivot plus a usage_count bump (question.md §XII).
     *
     * @param  Collection<int, Question>  $questions
     */
    private function recordUsage(Exam $exam, Collection $questions): void
    {
        $now = now();

        foreach ($questions as $order => $question) {
            DB::table('edu_exam_question')->insert([
                'exam_id' => $exam->id,
                'question_id' => $question->id,
                'order' => $order + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            QuestionStatistic::firstOrCreate(['question_id' => $question->id]);
            QuestionStatistic::where('question_id', $question->id)->increment('usage_count');
        }
    }
}
