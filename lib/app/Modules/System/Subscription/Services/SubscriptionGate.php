<?php

namespace App\Modules\System\Subscription\Services;

use App\Modules\System\Subscription\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Answers "may this business do X under its current plan?" for subscription
 * status and per-resource quota.
 *
 * Grandfathering: a business with no subscription row is treated as unlimited
 * and active, so tenants not yet onboarded to billing keep working. Quota and
 * expiry only bite once a business has an actual subscription.
 */
class SubscriptionGate
{
    /**
     * Quota resource key => table holding that business's rows.
     */
    private const RESOURCES = [
        'students' => 'edu_students',
        'classes' => 'edu_classes',
        'teachers' => 'hr_teachers',
        'courses' => 'edu_courses',
        'branches' => 'sys_branches',
        'parents' => 'crm_parents',
    ];

    /**
     * The business's active subscription, or null when it has none.
     */
    public function activeSubscription(int $businessId): ?Subscription
    {
        return Subscription::where('business_id', $businessId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->with('package')
            ->latest('started_at')
            ->first();
    }

    /**
     * Whether the business may currently perform billable actions: it either
     * has no subscription (grandfathered) or a non-expired active one.
     */
    public function hasActiveAccess(int $businessId): bool
    {
        $hasAnySubscription = Subscription::where('business_id', $businessId)->exists();

        if (! $hasAnySubscription) {
            return true;
        }

        $active = $this->activeSubscription($businessId);

        if (! $active) {
            return false;
        }

        return $active->expires_at === null || ! $active->expires_at->isPast();
    }

    /**
     * Whether the business's plan entitles it to a feature (e.g. "assignments",
     * "advanced_reports"). Grandfathering: no active subscription => all features.
     * A package with null feature_keys is treated as "not configured" => all
     * features (backward compatible); an explicit array restricts to its members.
     */
    public function hasFeature(string $feature, int $businessId): bool
    {
        $subscription = $this->activeSubscription($businessId);

        if (! $subscription || ! $subscription->package) {
            return true;
        }

        $keys = $subscription->package->feature_keys;

        if ($keys === null) {
            return true;
        }

        return in_array($feature, (array) $keys, true);
    }

    /**
     * The cap for a resource under the current plan. null means unlimited
     * (also returned when the business is grandfathered with no plan).
     */
    public function limit(string $resource, int $businessId): ?int
    {
        $subscription = $this->activeSubscription($businessId);

        if (! $subscription || ! $subscription->package) {
            return null;
        }

        $limits = $subscription->package->limits ?? [];
        $value = $limits[$resource] ?? null;

        return $value === null ? null : (int) $value;
    }

    /**
     * Current usage count of a resource for the business.
     */
    public function usage(string $resource, int $businessId): int
    {
        $table = self::RESOURCES[$resource] ?? null;

        if (! $table) {
            return 0;
        }

        $query = DB::table($table)->where('business_id', $businessId);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return (int) $query->count();
    }

    /**
     * Whether adding $adding more of $resource stays within the plan's cap.
     */
    public function allows(string $resource, int $businessId, int $adding = 1): bool
    {
        $limit = $this->limit($resource, $businessId);

        if ($limit === null) {
            return true;
        }

        return $this->usage($resource, $businessId) + $adding <= $limit;
    }

    /**
     * Remaining headroom for a resource, or null when unlimited.
     */
    public function remaining(string $resource, int $businessId): ?int
    {
        $limit = $this->limit($resource, $businessId);

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->usage($resource, $businessId));
    }
}
