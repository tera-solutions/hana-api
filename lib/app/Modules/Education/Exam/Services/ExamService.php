<?php

namespace App\Modules\Education\Exam\Services;

use App\Helpers\Task;
use App\Modules\Education\Exam\Models\Exam;
use App\Modules\Education\Exam\Models\ExamQuestion;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class ExamService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable exam bank (exam.md §XV).
     */
    public function paginate(array $params = [])
    {
        $query = Exam::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('exam_code', 'like', "%{$search}%")
                    ->orWhere('exam_name', 'like', "%{$search}%");
            });
        }

        foreach (['exam_type', 'course_id', 'level_id', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if ($scope = TeacherScope::current()) {
            $scope->constrainExams($query);
        }

        $this->applySort($query, $params, ['exam_code', 'exam_name', 'exam_type', 'status', 'created_at']);

        return $query->with(['course', 'level'])->withCount('questions')->paginate($this->resolvePerPage($params));
    }

    public function find($id): Exam
    {
        return Exam::with(['course', 'level', 'questions'])->findOrFail($id);
    }

    /**
     * Exam detail, scoped for teachers (exam.md §VI).
     *
     * @throws \Package\Exception\AuthorizationException
     */
    public function detail($id): Exam
    {
        $exam = $this->find($id);

        $this->authorize($exam);

        return $exam;
    }

    public function create(array $data): Exam
    {
        return DB::transaction(function () use ($data) {
            $exam = new Exam($data);
            $exam->exam_code = $this->generateCode();
            $exam->version = 1;
            $exam->status = Exam::STATUS_DRAFT;
            $exam->save();

            return $this->find($exam->id);
        });
    }

    /**
     * Update an exam. Editing one already in use (published or with a scheduled session)
     * leaves it immutable and forks the edit into a new draft version within the lineage
     * (exam.md §IV "Version đề thi"); drafts are edited in place.
     */
    public function update($id, array $data): Exam
    {
        return DB::transaction(function () use ($id, $data) {
            $exam = Exam::with('questions')->findOrFail($id);

            $this->authorize($exam);

            unset($data['id'], $data['exam_code'], $data['version'], $data['root_exam_id'], $data['status']);

            if ($this->isInUse($exam)) {
                $rootId = $this->rootId($exam);
                $nextVersion = (int) $this->lineageQuery($rootId)->max('version') + 1;

                $copy = $this->duplicate($exam, version: $nextVersion, rootExamId: $rootId);
                $copy->update($data);

                return $this->find($copy->id);
            }

            $exam->update($data);

            return $this->find($exam->id);
        });
    }

    /**
     * Create an exam seeded with questions drawn from the question bank
     * (question.md §XI "Generate Exam"). $questionRows are pre-mapped to the
     * edu_exam_questions shape (skill, question_type, content, answer_key, score, difficulty).
     *
     * @param  array<int, array<string, mixed>>  $questionRows
     */
    public function createFromBank(array $data, array $questionRows): Exam
    {
        return DB::transaction(function () use ($data, $questionRows) {
            $exam = new Exam($data);
            $exam->exam_code = $this->generateCode();
            $exam->version = 1;
            $exam->status = Exam::STATUS_DRAFT;
            $exam->save();

            foreach ($questionRows as $row) {
                $exam->questions()->create($row);
            }

            return $this->find($exam->id);
        });
    }

    public function delete($id): void
    {
        $exam = Exam::findOrFail($id);

        $this->authorize($exam);

        $exam->delete();
    }

    /**
     * Clone an exam into a brand-new, standalone draft — a fresh lineage starting at
     * version 1 (exam.md §IV "Clone đề thi").
     */
    public function clone($id): Exam
    {
        return DB::transaction(function () use ($id) {
            $source = Exam::with('questions')->findOrFail($id);

            $this->authorize($source);

            $copy = $this->duplicate($source, version: 1, rootExamId: null);

            return $this->find($copy->id);
        });
    }

    /**
     * Replicate an exam (and its questions) into a new draft. Shared by clone (standalone)
     * and the version lineage (ExamVersion module).
     */
    public function duplicate(Exam $source, int $version, ?int $rootExamId): Exam
    {
        $copy = $source->replicate(['exam_code', 'created_by', 'updated_by', 'deleted_by']);
        $copy->exam_code = $this->generateCode();
        $copy->version = $version;
        $copy->root_exam_id = $rootExamId;
        $copy->status = Exam::STATUS_DRAFT;
        $copy->save();

        foreach ($source->questions as $question) {
            $clone = $question->replicate(['created_by', 'updated_by', 'deleted_by']);
            $clone->exam_id = $copy->id;
            $clone->save();
        }

        return $copy;
    }

    // ── Versioning (exam.md §IV "Version đề thi") ────────────────────────────────

    /**
     * Every version in an exam's lineage, oldest first.
     *
     * @return Collection<int, Exam>
     */
    public function lineageFor($examId): Collection
    {
        $exam = Exam::findOrFail($examId);

        return $this->lineageQuery($this->rootId($exam))
            ->with(['course', 'level'])
            ->orderBy('version')
            ->get();
    }

    /**
     * An exam is "in use" once it leaves draft or has a scheduled session — editing it
     * then forks a new draft version instead of mutating it.
     */
    private function isInUse(Exam $exam): bool
    {
        return $exam->status !== Exam::STATUS_DRAFT || $exam->sessions()->exists();
    }

    private function rootId(Exam $exam): int
    {
        return (int) ($exam->root_exam_id ?? $exam->id);
    }

    /**
     * The lineage root row plus every version pointing at it.
     */
    private function lineageQuery(int $rootId)
    {
        return Exam::where('id', $rootId)->orWhere('root_exam_id', $rootId);
    }

    // ── Questions (exam.md §VII) ─────────────────────────────────────────────────

    public function addQuestion($examId, array $data): ExamQuestion
    {
        $exam = Exam::findOrFail($examId);

        $this->authorize($exam);

        $data['exam_id'] = $exam->id;

        return ExamQuestion::create($data);
    }

    public function updateQuestion($questionId, array $data): ExamQuestion
    {
        $question = ExamQuestion::with('exam')->findOrFail($questionId);

        $this->authorize($question->exam);

        unset($data['id'], $data['exam_id']);

        $question->update($data);

        return $question->fresh();
    }

    public function deleteQuestion($questionId): void
    {
        $question = ExamQuestion::with('exam')->findOrFail($questionId);

        $this->authorize($question->exam);

        $question->delete();
    }

    /**
     * TeacherScope guard shared by every single-exam write/read (exam.md §VI).
     *
     * @throws \Package\Exception\AuthorizationException
     */
    private function authorize(Exam $exam): void
    {
        if ($scope = TeacherScope::current()) {
            $scope->authorizeExam(
                (int) $exam->id,
                $exam->course_id ? (int) $exam->course_id : null,
                $exam->created_by ? (int) $exam->created_by : null,
            );
        }
    }

    /**
     * Generate the next human-readable exam code (e.g. EXM000001).
     */
    private function generateCode(): string
    {
        $count = Task::setAndGetReferenceCount('exam');

        return Task::generateReferenceNumber('exam', $count, 'EXM');
    }
}
