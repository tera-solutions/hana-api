<?php

namespace App\Modules\Education\EvaluationCriteriaTemplate\Services;

use App\Modules\Education\EvaluationCriteriaTemplate\Models\EvaluationCriteriaTemplate;
use Illuminate\Support\Facades\Auth;
use Package\Database\Concerns\HandlesEntityQueries;

/**
 * A teacher only ever sees templates that are shared business-wide or that
 * they authored themselves; only an `is_admin` account may author (or flip
 * on) a shared one — mirrors the self-vs-admin scoping already established
 * for Payroll/Wallet Request.
 */
class EvaluationCriteriaTemplateService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = EvaluationCriteriaTemplate::query();

        $userId = Auth::guard('api')->id();
        $query->where(function ($q) use ($userId) {
            $q->where('is_shared', true)->orWhere('created_by', $userId);
        });

        foreach (['evaluation_type', 'status'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['search'])) {
            $query->where('name', 'like', '%'.$params['search'].'%');
        }

        $this->applySort($query, $params, ['name', 'evaluation_type', 'created_at']);

        return $query->orderByDesc('is_shared')->paginate($this->resolvePerPage($params));
    }

    public function find($id): EvaluationCriteriaTemplate
    {
        return EvaluationCriteriaTemplate::findOrFail($id);
    }

    public function create(array $data): EvaluationCriteriaTemplate
    {
        $template = new EvaluationCriteriaTemplate($data);
        $template->status = EvaluationCriteriaTemplate::STATUS_ACTIVE;
        $template->is_shared = $this->isAdmin() && ! empty($data['is_shared']);
        $template->save();

        return $this->find($template->id);
    }

    /**
     * @throws \RuntimeException
     */
    public function update($id, array $data): EvaluationCriteriaTemplate
    {
        $template = $this->find($id);
        $this->assertOwnOrAdmin($template);

        unset($data['id'], $data['business_id'], $data['evaluation_type'], $data['status']);

        if (array_key_exists('is_shared', $data)) {
            $data['is_shared'] = $this->isAdmin() && $data['is_shared'];
        }

        $template->update($data);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function suspend($id): EvaluationCriteriaTemplate
    {
        $template = $this->find($id);
        $this->assertOwnOrAdmin($template);

        if ($template->status === EvaluationCriteriaTemplate::STATUS_INACTIVE) {
            throw new \RuntimeException('Bảng tiêu chí đang ở trạng thái ngừng.');
        }

        $template->update(['status' => EvaluationCriteriaTemplate::STATUS_INACTIVE]);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function restore($id): EvaluationCriteriaTemplate
    {
        $template = $this->find($id);
        $this->assertOwnOrAdmin($template);

        if ($template->status !== EvaluationCriteriaTemplate::STATUS_INACTIVE) {
            throw new \RuntimeException('Chỉ có thể khôi phục bảng tiêu chí đang ngừng.');
        }

        $template->update(['status' => EvaluationCriteriaTemplate::STATUS_ACTIVE]);

        return $this->find($id);
    }

    private function isAdmin(): bool
    {
        return (bool) (Auth::guard('api')->user()?->is_admin);
    }

    /**
     * @throws \RuntimeException
     */
    private function assertOwnOrAdmin(EvaluationCriteriaTemplate $template): void
    {
        if ($this->isAdmin()) {
            return;
        }

        if ((int) $template->created_by !== (int) Auth::guard('api')->id()) {
            throw new \RuntimeException('Bạn chỉ có thể sửa bảng tiêu chí do chính mình tạo.');
        }
    }
}
