<?php

namespace App\Modules\Education\ClassSessionFeedback\Services;

use App\Modules\Education\ClassSession\Models\ClassSession;
use App\Modules\Education\ClassSessionFeedback\Models\ClassSessionFeedback;
use Illuminate\Database\Eloquent\Builder;
use Package\Database\Concerns\HandlesEntityQueries;

class ClassSessionFeedbackService
{
    use HandlesEntityQueries;

    private const RELATIONS = ['session', 'student'];

    /**
     * Paginated, filterable per-student session notes ("Ghi chú học viên").
     */
    public function paginate(array $params = [])
    {
        $query = $this->filteredQuery($params);

        $this->applySort($query, $params, ['created_at']);

        return $query->with(self::RELATIONS)->paginate($this->resolvePerPage($params));
    }

    private function filteredQuery(array $params): Builder
    {
        $query = ClassSessionFeedback::query();

        foreach (['session_id', 'student_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['class_id'])) {
            $query->whereHas('session', fn ($q) => $q->where('class_id', $params['class_id']));
        }

        return $query;
    }

    /**
     * One row per (session, student) — `updateOrCreate` so re-saving a note
     * for the same student in the same session updates it in place.
     */
    public function create(array $data): ClassSessionFeedback
    {
        $sessionId = (int) ($data['session_id'] ?? 0);
        $studentId = (int) ($data['student_id'] ?? 0);

        $session = ClassSession::findOrFail($sessionId);

        $feedback = ClassSessionFeedback::updateOrCreate(
            ['session_id' => $sessionId, 'student_id' => $studentId],
            array_filter(
                ['rating' => $data['rating'] ?? null, 'comment' => $data['comment'] ?? null],
                fn ($v, $k) => array_key_exists($k, $data),
                ARRAY_FILTER_USE_BOTH,
            ),
        );

        return $feedback->fresh(self::RELATIONS);
    }
}
