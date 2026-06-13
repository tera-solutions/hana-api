<?php

namespace App\Modules\CRM\Lead\Services;

use App\Modules\CRM\Lead\Models\LeadStudent;
use Package\Database\Concerns\HandlesEntityQueries;

class LeadStudentService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable list of the students linked to a lead.
     */
    public function paginate($leadId, array $params = [])
    {
        $query = LeadStudent::query()->where('lead_id', $leadId);

        // Search: student id/name/code.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('edu_students.id', $search)
                    ->orWhere('edu_students.code', 'like', "%{$search}%")
                    ->orWhere('edu_students.name', 'like', "%{$search}%");
            });
        }

        if (array_key_exists('relationship', $params) && $params['relationship'] !== '' && $params['relationship'] !== null) {
            $query->where('relationship', $params['relationship']);
        }

        $this->applySort($query, $params, ['relationship', 'created_at']);

        return $query->with(['student'])->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return LeadStudent::with(['lead', 'student'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $link = LeadStudent::create($data);

        return $this->find($link->id);
    }

    public function update($id, array $data)
    {
        $link = $this->find($id);

        // Lead and Student cannot be reassigned once linked; only the
        // relationship is editable (see lead.md §9).
        unset($data['id'], $data['lead_id'], $data['student_id']);

        $link->update($data);

        return $this->find($link->id);
    }

    /**
     * Soft delete the link (gỡ liên kết). A lead may have any number of linked
     * students, so there is no minimum-link guard here.
     */
    public function delete($id)
    {
        return $this->find($id)->delete();
    }
}
