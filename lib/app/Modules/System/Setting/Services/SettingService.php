<?php

namespace App\Modules\System\Setting\Services;

use App\Modules\System\Setting\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Package\Database\Concerns\HandlesEntityQueries;

class SettingService
{
    use HandlesEntityQueries;

    /**
     * Paginated, searchable, filterable list scoped to the acting user's business.
     */
    public function paginate(array $params = [])
    {
        $query = Setting::query()->where('business_id', $this->actingBusinessId());

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        foreach (['group', 'type'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        $this->applySort($query, $params, ['key', 'group', 'created_at']);

        return $query->paginate($this->resolvePerPage($params));
    }

    public function find($id): Setting
    {
        return Setting::where('business_id', $this->actingBusinessId())->findOrFail($id);
    }

    public function create(array $data): Setting
    {
        $data['business_id'] = $this->actingBusinessId();
        $data['type'] = $data['type'] ?? 'string';

        return Setting::create($data);
    }

    public function update($id, array $data): Setting
    {
        $setting = $this->find($id);

        unset($data['id'], $data['key'], $data['business_id']);

        $setting->update($data);

        return $setting;
    }

    /**
     * Create-or-update by `key` within the acting user's business — the FE settings
     * page toggles/selects call this so it doesn't need to know the row id up front.
     */
    public function upsert(array $data): Setting
    {
        $businessId = $this->actingBusinessId();

        $setting = Setting::where('business_id', $businessId)->where('key', $data['key'])->first();

        if ($setting) {
            $setting->update([
                'value' => $data['value'] ?? $setting->value,
                'type' => $data['type'] ?? $setting->type,
                'group' => $data['group'] ?? $setting->group,
                'label' => $data['label'] ?? $setting->label,
            ]);

            return $setting;
        }

        return Setting::create([
            'business_id' => $businessId,
            'key' => $data['key'],
            'value' => $data['value'] ?? null,
            'type' => $data['type'] ?? 'string',
            'group' => $data['group'] ?? null,
            'label' => $data['label'] ?? null,
        ]);
    }

    public function delete($id): void
    {
        $this->find($id)->delete();
    }

    private function actingBusinessId(): ?int
    {
        $user = Auth::guard('api')->user() ?? Auth::user();

        return $user?->business_id;
    }
}
