<?php

namespace App\Modules\Finance\SubscriptionPackage\Services;

use App\Modules\Finance\SubscriptionPackage\Models\SubscriptionPackage;
use App\Modules\Finance\SubscriptionPackage\Models\SubscriptionPackageDiscountRule;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class SubscriptionPackageService
{
    use HandlesEntityQueries;

    public function paginate(array $params = [])
    {
        $query = SubscriptionPackage::query();

        foreach (['status', 'type'] as $filter) {
            if (! empty($params[$filter])) {
                $query->where($filter, $params[$filter]);
            }
        }

        if (! empty($params['search'])) {
            $query->where('name', 'like', '%'.$params['search'].'%');
        }

        $this->applySort($query, $params, ['name', 'type', 'price', 'status', 'created_at']);

        return $query->withCount('discountRules')->paginate($this->resolvePerPage($params));
    }

    public function summary(): array
    {
        return [
            'total' => SubscriptionPackage::count(),
            'active' => SubscriptionPackage::where('status', SubscriptionPackage::STATUS_ACTIVE)->count(),
            'inactive' => SubscriptionPackage::where('status', SubscriptionPackage::STATUS_INACTIVE)->count(),
        ];
    }

    public function find($id): SubscriptionPackage
    {
        return SubscriptionPackage::with('discountRules')->findOrFail($id);
    }

    public function create(array $data): SubscriptionPackage
    {
        $package = new SubscriptionPackage($data);
        $package->status = SubscriptionPackage::STATUS_ACTIVE;
        $package->save();

        return $this->find($package->id);
    }

    public function update($id, array $data): SubscriptionPackage
    {
        $package = $this->find($id);

        unset($data['id'], $data['status'], $data['discount_rules']);

        $package->update($data);

        return $this->find($package->id);
    }

    /**
     * @throws \RuntimeException
     */
    public function toggle($id): SubscriptionPackage
    {
        $package = $this->find($id);

        $package->update([
            'status' => $package->status === SubscriptionPackage::STATUS_ACTIVE
                ? SubscriptionPackage::STATUS_INACTIVE
                : SubscriptionPackage::STATUS_ACTIVE,
        ]);

        return $this->find($id);
    }

    /**
     * @throws \RuntimeException
     */
    public function delete($id): void
    {
        $package = $this->find($id);

        if ($package->enrollments()->exists()) {
            throw new \RuntimeException('Gói đang được sử dụng, không thể xóa.');
        }

        $package->delete();
    }

    public function usages($id): array
    {
        $package = $this->find($id);

        $usages = $package->enrollments()
            ->with(['student', 'course'])
            ->get()
            ->map(fn ($enrollment) => [
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student?->name,
                'course' => $enrollment->course?->name,
                'started_at' => $enrollment->enrolled_at,
            ]);

        return ['total' => $usages->count(), 'data' => $usages->values()->all()];
    }

    public function setDiscountRules($id, array $rules): SubscriptionPackage
    {
        return DB::transaction(function () use ($id, $rules) {
            $package = $this->find($id);

            $package->discountRules()->delete();

            foreach ($rules as $rule) {
                SubscriptionPackageDiscountRule::create([
                    'package_id' => $package->id,
                    'type' => $rule['type'],
                    'value' => $rule['value'],
                    'condition' => $rule['condition'] ?? null,
                    'enabled' => $rule['enabled'] ?? true,
                ]);
            }

            return $this->find($id);
        });
    }
}
