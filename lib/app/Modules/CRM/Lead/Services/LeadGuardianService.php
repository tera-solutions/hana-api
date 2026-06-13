<?php

namespace App\Modules\CRM\Lead\Services;

use App\Modules\CRM\Lead\Models\LeadGuardian;
use Package\Database\Concerns\HandlesEntityQueries;

class LeadGuardianService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable list of a lead's guardians.
     */
    public function paginate($leadId, array $params = [])
    {
        $query = LeadGuardian::query()->where('lead_id', $leadId);

        // Search: name / phone / email.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('relationship', $params) && $params['relationship'] !== '' && $params['relationship'] !== null) {
            $query->where('relationship', $params['relationship']);
        }

        $this->applySort($query, $params, ['full_name', 'relationship', 'created_at']);

        return $query->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return LeadGuardian::findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->find(LeadGuardian::create($data)->id);
    }

    public function update($id, array $data)
    {
        $guardian = $this->find($id);

        // The owning lead cannot be reassigned.
        unset($data['id'], $data['lead_id']);

        $guardian->update($data);

        return $this->find($guardian->id);
    }

    /**
     * Soft delete the guardian, blocked when it is the lead's last one
     * (lead.md §8: a lead must keep at least one guardian).
     *
     * @throws \RuntimeException when removing it would leave the lead with no guardian.
     */
    public function delete($id)
    {
        $guardian = $this->find($id);

        if (! $this->hasOtherGuardian($guardian)) {
            throw new \RuntimeException('Không thể xóa vì khách hàng phải còn ít nhất một người giám hộ.');
        }

        return $guardian->delete();
    }

    /**
     * Whether the lead has another (non-deleted) guardian besides this one.
     */
    private function hasOtherGuardian(LeadGuardian $guardian): bool
    {
        return LeadGuardian::where('lead_id', $guardian->lead_id)
            ->where('id', '!=', $guardian->id)
            ->exists();
    }
}
