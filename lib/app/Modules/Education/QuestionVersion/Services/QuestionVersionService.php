<?php

namespace App\Modules\Education\QuestionVersion\Services;

use App\Modules\Education\Question\Models\Question;
use App\Modules\Education\QuestionVersion\Models\QuestionVersion;
use Illuminate\Database\Eloquent\Collection;

class QuestionVersionService
{
    /**
     * Version history of a question, newest first (question.md §IV "Version câu hỏi").
     *
     * @return Collection<int, QuestionVersion>
     */
    public function listForQuestion($questionId): Collection
    {
        Question::findOrFail($questionId);

        return QuestionVersion::where('question_id', $questionId)->orderByDesc('version')->get();
    }

    public function find($id): QuestionVersion
    {
        return QuestionVersion::with('question')->findOrFail($id);
    }

    /**
     * Snapshot a question's current content as a new version row — called from the
     * update flow when an in-use question is edited (question.md §IX BR007).
     */
    public function record(Question $question, ?string $changeLog): QuestionVersion
    {
        return QuestionVersion::create([
            'question_id' => $question->id,
            'version' => $question->version,
            'snapshot' => [
                'content' => $question->content,
                'explanation' => $question->explanation,
                'answers' => $question->answers->map->only(['answer_key', 'answer_content', 'is_correct', 'sort_order'])->all(),
            ],
            'change_log' => $changeLog,
        ]);
    }
}
