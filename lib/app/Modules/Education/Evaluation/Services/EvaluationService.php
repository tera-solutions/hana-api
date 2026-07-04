<?php

namespace App\Modules\Education\Evaluation\Services;

use App\Modules\Education\Evaluation\Enums\EvaluationType;
use App\Modules\Education\Evaluation\Models\Evaluation;
use App\Modules\Education\Support\TeacherScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * All evaluation business logic (evaluation.md). Enforces one-per-period uniqueness
 * (BR-01), the lock-on-edit rule (BR-02) and the auto-computed total score /
 * classification from the per-criterion ratings (BR-03).
 */
class EvaluationService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['course', 'classRoom', 'lesson'];

    public function paginate(array $params = [])
    {
        $query = Evaluation::query();

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('evaluation_code', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%");
            });
        }

        foreach (['evaluation_type', 'evaluator_type', 'evaluator_id', 'target_id', 'course_id', 'class_room_id', 'lesson_id', 'evaluation_period', 'classification', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['score_from'])) {
            $query->where('score', '>=', $params['score_from']);
        }
        if (! empty($params['score_to'])) {
            $query->where('score', '<=', $params['score_to']);
        }
        if (! empty($params['evaluated_from'])) {
            $query->whereDate('evaluated_at', '>=', $params['evaluated_from']);
        }
        if (! empty($params['evaluated_to'])) {
            $query->whereDate('evaluated_at', '<=', $params['evaluated_to']);
        }

        if ($scope = TeacherScope::current()) {
            $scope->constrainByClass($query, 'class_room_id');
        }

        $this->applySort($query, $params, ['evaluation_code', 'score', 'evaluated_at', 'status', 'created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    public function find($id): Evaluation
    {
        $query = Evaluation::query();

        if ($scope = TeacherScope::current()) {
            $scope->constrainByClass($query, 'class_room_id');
        }

        return $query->with(self::RELATIONS)->findOrFail($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function create(array $data): Evaluation
    {
        return DB::transaction(function () use ($data) {
            if (($scope = TeacherScope::current()) && ! empty($data['class_room_id'])) {
                $scope->authorizeClass((int) $data['class_room_id']);
            }

            $type = EvaluationType::from($data['evaluation_type']);
            $this->assertCriteriaBelongToType($type, $data['criteria'] ?? []);
            $this->assertNotSelfEvaluation($data);
            $this->assertUniquePerPeriod($data);

            $data = $this->withComputedScore($data);
            $data['evaluation_code'] = $this->generateCode();
            $data['status'] = Evaluation::STATUS_DRAFT;
            $data['evaluated_at'] = $data['evaluated_at'] ?? now();

            $evaluation = Evaluation::create($data);

            return $this->find($evaluation->id);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function update($id, array $data): Evaluation
    {
        return DB::transaction(function () use ($id, $data) {
            $evaluation = $this->find($id);

            if ($evaluation->status === Evaluation::STATUS_LOCKED) {
                throw new \RuntimeException('Không thể sửa đánh giá đã khóa.'); // BR-02
            }

            if (array_key_exists('criteria', $data)) {
                $type = EvaluationType::from($data['evaluation_type'] ?? $evaluation->evaluation_type);
                $this->assertCriteriaBelongToType($type, $data['criteria'] ?? []);
                $data = $this->withComputedScore($data, $evaluation);
            }

            $evaluation->update($data);

            return $this->find($id);
        });
    }

    /**
     * @throws \RuntimeException
     */
    public function delete($id): void
    {
        $evaluation = $this->find($id);

        if ($evaluation->status === Evaluation::STATUS_LOCKED) {
            throw new \RuntimeException('Không thể xóa đánh giá đã khóa.'); // BR-02
        }

        $evaluation->delete();
    }

    public function submit($id): Evaluation
    {
        return $this->transition($id, Evaluation::STATUS_SUBMITTED, [Evaluation::STATUS_DRAFT, Evaluation::STATUS_REJECTED], 'Chỉ có thể gửi đánh giá ở trạng thái nháp hoặc bị từ chối.');
    }

    public function approve($id): Evaluation
    {
        return $this->transition($id, Evaluation::STATUS_APPROVED, [Evaluation::STATUS_SUBMITTED], 'Chỉ có thể duyệt đánh giá đã gửi.');
    }

    public function reject($id): Evaluation
    {
        return $this->transition($id, Evaluation::STATUS_REJECTED, [Evaluation::STATUS_SUBMITTED], 'Chỉ có thể từ chối đánh giá đã gửi.');
    }

    public function lock($id): Evaluation
    {
        return $this->transition($id, Evaluation::STATUS_LOCKED, [Evaluation::STATUS_APPROVED], 'Chỉ có thể khóa đánh giá đã duyệt.');
    }

    /**
     * @param  string[]  $allowedFrom
     *
     * @throws \RuntimeException
     */
    private function transition($id, string $to, array $allowedFrom, string $error): Evaluation
    {
        return DB::transaction(function () use ($id, $to, $allowedFrom, $error) {
            $evaluation = $this->find($id);

            if (! in_array($evaluation->status, $allowedFrom, true)) {
                throw new \RuntimeException($error);
            }

            $evaluation->update(['status' => $to]);

            return $this->find($id);
        });
    }

    /**
     * BR-03: the total score is the average of the per-criterion ratings (1-5 scale);
     * classification is derived from it.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withComputedScore(array $data, ?Evaluation $existing = null): array
    {
        $criteria = $data['criteria'] ?? $existing?->criteria ?? [];
        $scores = array_filter(array_map(
            fn ($row) => is_array($row) && isset($row['score']) ? (float) $row['score'] : null,
            $criteria
        ), fn ($s) => $s !== null);

        if ($scores === []) {
            $data['score'] = null;
            $data['classification'] = null;

            return $data;
        }

        $average = round(array_sum($scores) / count($scores), 2);
        $type = $data['evaluation_type'] ?? $existing?->evaluation_type;

        $data['score'] = $average;
        $data['classification'] = $this->classify((string) $type, $average);

        return $data;
    }

    private function classify(string $type, float $average): string
    {
        return match (true) {
            $average >= 4.5 => 'excellent',
            $average >= 3.5 => 'good',
            $average >= 2.5 => 'average',
            default => $type === Evaluation::TYPE_PARENT ? 'warning' : 'weak',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $criteria
     *
     * @throws \RuntimeException
     */
    private function assertCriteriaBelongToType(EvaluationType $type, array $criteria): void
    {
        $allowed = $type->criteria();

        foreach ($criteria as $row) {
            $key = is_array($row) ? ($row['criterion'] ?? null) : null;
            if ($key !== null && ! in_array($key, $allowed, true)) {
                throw new \RuntimeException("Tiêu chí \"{$key}\" không hợp lệ cho loại đánh giá này.");
            }
        }
    }

    /**
     * Reject self-evaluation: the evaluator and the target are the same person only when
     * they are the same kind of entity (same type) with the same id.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \RuntimeException
     */
    private function assertNotSelfEvaluation(array $data): void
    {
        $sameKind = ($data['evaluator_type'] ?? null) === ($data['evaluation_type'] ?? null);
        $sameId = ! empty($data['evaluator_id']) && (int) $data['evaluator_id'] === (int) ($data['target_id'] ?? 0);

        if ($sameKind && $sameId) {
            throw new \RuntimeException('Không thể tự đánh giá chính mình.');
        }
    }

    /**
     * BR-01: one evaluator may evaluate a target once per period (same type/context).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \RuntimeException
     */
    private function assertUniquePerPeriod(array $data): void
    {
        $exists = Evaluation::query()
            ->where('evaluation_type', $data['evaluation_type'])
            ->where('target_id', $data['target_id'])
            ->where('evaluator_type', $data['evaluator_type'])
            ->where('evaluator_id', $data['evaluator_id'] ?? null)
            ->where('evaluation_period', $data['evaluation_period'])
            ->when(! empty($data['lesson_id']), fn (Builder $q) => $q->where('lesson_id', $data['lesson_id']))
            ->when(! empty($data['class_room_id']), fn (Builder $q) => $q->where('class_room_id', $data['class_room_id']))
            ->exists();

        if ($exists) {
            throw new \RuntimeException('Đã tồn tại đánh giá cho đối tượng này trong cùng kỳ.'); // BR-01
        }
    }

    private function generateCode(): string
    {
        $next = (int) (Evaluation::withTrashed()->max('id') ?? 0) + 1;

        return 'EVAL'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
