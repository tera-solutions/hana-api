<?php

namespace App\Modules\CRM\Lead\Services;

use App\Helpers\Task;
use App\Modules\CRM\Lead\Events\LeadCreated;
use App\Modules\CRM\Lead\Models\Lead;
use App\Modules\CRM\Lead\Models\LeadHistory;
use App\Modules\CRM\Lead\Models\LeadStudent;
use App\Modules\Education\Student\Services\StudentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class LeadService
{
    use HandlesEntityQueries;

    /**
     * Eager loads shared by detail/create/update responses.
     */
    private const RELATIONS = ['business', 'branch', 'owner', 'guardians', 'students', 'tags', 'courses'];

    /**
     * Paginated, searchable, filterable, sortable list (lead.md §2).
     */
    public function paginate(array $params = [])
    {
        $query = Lead::query();

        // Search: code, name, email, phone.
        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Scalar filters.
        foreach (['business_id', 'branch_id', 'status', 'source', 'owner_id'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        // Per-column text filters from the list spec.
        foreach (['name', 'email', 'phone'] as $field) {
            if (! empty($params[$field])) {
                $query->where($field, 'like', "%{$params[$field]}%");
            }
        }

        // Contact-date range ("Từ/Đến ngày liên hệ").
        if (! empty($params['contacted_from'])) {
            $query->whereDate('created_at', '>=', $params['contacted_from']);
        }
        if (! empty($params['contacted_to'])) {
            $query->whereDate('created_at', '<=', $params['contacted_to']);
        }

        // Multi-select tag / course filters.
        if (! empty($params['tag_ids'])) {
            $tagIds = (array) $params['tag_ids'];
            $query->whereHas('tags', fn ($q) => $q->whereIn('crm_tags.id', $tagIds));
        }
        if (! empty($params['course_ids'])) {
            $courseIds = (array) $params['course_ids'];
            $query->whereHas('courses', fn ($q) => $q->whereIn('edu_courses.id', $courseIds));
        }

        $this->applySort($query, $params, ['code', 'name', 'status', 'created_at']);

        return $query->with(self::RELATIONS)
            ->withCount(['guardians', 'students'])
            ->paginate($this->resolvePerPage($params));
    }

    public function find($id)
    {
        return Lead::with(self::RELATIONS)->findOrFail($id);
    }

    /**
     * Detail with the full change history (lead.md §5).
     */
    public function detail($id): array
    {
        return [
            'lead' => $this->find($id),
            'histories' => LeadHistory::where('lead_id', $id)->latest()->get(),
        ];
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $relations = $this->extractRelations($data);

            $lead = new Lead($data);
            $lead->code = $this->generateCode($lead->business_id);
            $lead->status = Lead::STATUS_PENDING;
            $lead->save();

            $this->syncRelations($lead, $relations);

            $this->log($lead, 'created', null, $lead->status);

            event(new LeadCreated($lead));

            return $this->find($lead->id);
        });
    }

    public function update($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $lead = $this->find($id);

            // Immutable: identity & lifecycle (managed via suspend/restore).
            unset($data['id'], $data['code'], $data['business_id'], $data['status']);

            $relations = $this->extractRelations($data);

            $previousOwnerId = $lead->owner_id;

            $lead->update($data);

            $this->syncRelations($lead, $relations);

            $this->log($lead, 'updated');

            // Owner change is tracked separately (lead.md §4 business rules).
            if (array_key_exists('owner_id', $data) && (int) $data['owner_id'] !== (int) $previousOwnerId) {
                $this->logOwnerChange($lead, $previousOwnerId, $lead->owner_id);
            }

            return $this->find($lead->id);
        });
    }

    /**
     * Move a lead to "inactive" (lead.md §6 "Ngừng khách hàng").
     *
     * @throws \RuntimeException when the lead is already inactive.
     */
    public function suspend($id, array $data)
    {
        $lead = $this->find($id);

        if ($lead->status === Lead::STATUS_INACTIVE) {
            throw new \RuntimeException('Khách hàng đang ở trạng thái ngừng.');
        }

        $from = $lead->status;

        $lead->update([
            'previous_status' => $from,
            'status' => Lead::STATUS_INACTIVE,
            'suspended_at' => now(),
            'suspend_reason' => $data['reason'] ?? null,
            'suspended_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);

        $this->log($lead, 'suspended', $from, Lead::STATUS_INACTIVE, $data['reason'] ?? null, $data['note'] ?? null);

        return $this->find($lead->id);
    }

    /**
     * Reactivate an inactive lead, returning it to its pre-suspend status (or
     * "pending" when unknown) — lead.md §7.
     *
     * @throws \RuntimeException when the lead is not currently inactive.
     */
    public function restore($id, array $data = [])
    {
        $lead = $this->find($id);

        if ($lead->status !== Lead::STATUS_INACTIVE) {
            throw new \RuntimeException('Chỉ có thể khôi phục khách hàng đang ngừng.');
        }

        $to = $lead->previous_status ?: Lead::STATUS_PENDING;

        $lead->update([
            'status' => $to,
            'previous_status' => null,
            'suspended_at' => null,
            'suspend_reason' => null,
            'suspended_by' => null,
        ]);

        $this->log($lead, 'restored', Lead::STATUS_INACTIVE, $to, $data['reason'] ?? null);

        return $this->find($lead->id);
    }

    /**
     * Move a lead through the care pipeline: pending → verified → consulting →
     * studying. "inactive" is out of scope — that's suspend/restore's job, since
     * those also carry a reason and a pre-suspend status to return to.
     *
     * @throws \RuntimeException when the lead is currently inactive.
     */
    public function updateStatus($id, array $data)
    {
        $lead = $this->find($id);

        if ($lead->status === Lead::STATUS_INACTIVE) {
            throw new \RuntimeException('Khách hàng đang ở trạng thái ngừng, hãy khôi phục trước.');
        }

        $from = $lead->status;
        $lead->update(['status' => $data['status']]);

        $this->log($lead, 'status_changed', $from, $lead->status, null, $data['note'] ?? null);

        return $this->find($lead->id);
    }

    /**
     * Convert a lead into a student: creates the student record (reusing
     * StudentService so code generation / status / events stay consistent),
     * links it back to the lead, and moves the lead to "studying".
     *
     * Every payload field is an override on top of the lead's own data —
     * only dob/gender/branch_id are actually required by student creation,
     * so those three raise a clear error when neither the lead nor the
     * payload has them.
     *
     * @throws \RuntimeException when the lead is inactive or required student
     *                           fields (dob/gender/branch) are missing from
     *                           both the lead and the override payload.
     */
    public function convert($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $lead = $this->find($id);

            if ($lead->status === Lead::STATUS_INACTIVE) {
                throw new \RuntimeException('Không thể chuyển đổi khách hàng đang ở trạng thái ngừng.');
            }

            $dob = $data['dob'] ?? optional($lead->dob)->format('Y-m-d');
            $gender = $data['gender'] ?? $lead->gender;
            $branchId = $data['branch_id'] ?? $lead->branch_id;

            if (! $dob || ! $gender || ! $branchId) {
                throw new \RuntimeException('Thiếu thông tin bắt buộc (ngày sinh, giới tính, chi nhánh) để chuyển đổi thành học viên.');
            }

            $student = app(StudentService::class)->create([
                'name' => $lead->name,
                'dob' => $dob,
                'gender' => $gender,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'business_id' => $lead->business_id,
                'branch_id' => $branchId,
                'level_id' => $data['level_id'] ?? null,
                'enrollment_date' => $data['enrollment_date'] ?? now()->toDateString(),
                'admission_source' => $lead->source,
            ]);

            LeadStudent::create([
                'lead_id' => $lead->id,
                'student_id' => $student->id,
                'relationship' => 'self',
            ]);

            $from = $lead->status;
            $lead->update(['status' => Lead::STATUS_STUDYING]);

            $this->log($lead, 'converted', $from, Lead::STATUS_STUDYING, null, $data['note'] ?? null);

            return [
                'lead' => $this->find($lead->id),
                'student_id' => $student->id,
            ];
        });
    }

    /**
     * Pull the nested relation payloads out of $data so the remainder maps
     * straight onto the lead's own columns.
     *
     * @return array{guardians:?array,students:?array,tag_ids:?array,course_ids:?array}
     */
    private function extractRelations(array &$data): array
    {
        $relations = [
            'guardians' => array_key_exists('guardians', $data) ? $data['guardians'] : null,
            'students' => array_key_exists('students', $data) ? $data['students'] : null,
            'tag_ids' => array_key_exists('tag_ids', $data) ? $data['tag_ids'] : null,
            'course_ids' => array_key_exists('course_ids', $data) ? $data['course_ids'] : null,
        ];

        unset($data['guardians'], $data['students'], $data['tag_ids'], $data['course_ids']);

        return $relations;
    }

    /**
     * Apply the nested relation payloads. A `null` payload means "not provided",
     * so the existing links are left untouched.
     */
    private function syncRelations(Lead $lead, array $relations): void
    {
        if (is_array($relations['tag_ids'])) {
            $lead->tags()->sync($relations['tag_ids']);
        }

        if (is_array($relations['course_ids'])) {
            $lead->courses()->sync($relations['course_ids']);
        }

        if (is_array($relations['students'])) {
            $this->syncStudents($lead, $relations['students']);
        }

        if (is_array($relations['guardians'])) {
            $this->syncGuardians($lead, $relations['guardians']);
        }
    }

    /**
     * Replace the lead's student links, preserving the relationship type.
     *
     * @param  array<int, array<string, mixed>>  $students
     */
    private function syncStudents(Lead $lead, array $students): void
    {
        $sync = [];

        foreach ($students as $student) {
            if (empty($student['student_id'])) {
                continue;
            }

            $sync[$student['student_id']] = ['relationship' => $student['relationship'] ?? null];
        }

        $lead->students()->sync($sync);
    }

    /**
     * Replace the lead's guardians wholesale (used by create / bulk update).
     * Granular add/update/remove lives in the dedicated §8 endpoints.
     *
     * @param  array<int, array<string, mixed>>  $guardians
     */
    private function syncGuardians(Lead $lead, array $guardians): void
    {
        $lead->guardians()->delete();

        foreach ($guardians as $guardian) {
            if (empty($guardian['full_name']) || empty($guardian['phone'])) {
                continue;
            }

            $lead->guardians()->create([
                'full_name' => $guardian['full_name'],
                'relationship' => $guardian['relationship'] ?? null,
                'phone' => $guardian['phone'],
                'email' => $guardian['email'] ?? null,
            ]);
        }
    }

    /**
     * Generate the next human-readable lead code (e.g. LEAD000001).
     */
    private function generateCode($businessId): string
    {
        $count = Task::setAndGetReferenceCount('lead', $businessId ?? 0);

        return Task::generateReferenceNumber('lead', $count, 'LEAD');
    }

    private function log(Lead $lead, string $action, $from = null, $to = null, $reason = null, $note = null): void
    {
        LeadHistory::create([
            'business_id' => $lead->business_id,
            'lead_id' => $lead->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'note' => $note,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }

    private function logOwnerChange(Lead $lead, $fromOwnerId, $toOwnerId): void
    {
        LeadHistory::create([
            'business_id' => $lead->business_id,
            'lead_id' => $lead->id,
            'action' => 'owner_changed',
            'from_owner_id' => $fromOwnerId,
            'to_owner_id' => $toOwnerId,
            'created_by' => Auth::guard('api')->id() ?? Auth::id(),
        ]);
    }
}
