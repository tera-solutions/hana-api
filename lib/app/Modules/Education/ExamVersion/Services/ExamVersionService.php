<?php

namespace App\Modules\Education\ExamVersion\Services;

use App\Modules\Education\Exam\Models\Exam;
use App\Modules\Education\Exam\Services\ExamService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-only view over an exam's version lineage (exam.md §IV "Version đề thi"). Versions are
 * created automatically when an in-use exam is edited; see {@see ExamService::update()}.
 */
class ExamVersionService
{
    public function __construct(private ExamService $examService) {}

    /**
     * Every version in an exam's lineage, oldest first.
     *
     * @return Collection<int, Exam>
     */
    public function listForExam($examId): Collection
    {
        return $this->examService->lineageFor($examId);
    }

    /**
     * A single exam version, with its questions.
     */
    public function find($id): Exam
    {
        return $this->examService->find($id);
    }
}
