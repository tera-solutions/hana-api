<?php

namespace App\Modules\System\Superadmin\Services;

use App\Modules\System\Business\Enums\BusinessStatus;
use App\Modules\System\Business\Models\Business;
use App\Modules\System\Business\Services\BusinessService;
use App\Modules\System\Subscription\Models\Subscription;
use App\Modules\System\Subscription\Models\SubscriptionInvoice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Package\Database\Concerns\HandlesEntityQueries;
use Package\Tenancy\TenantContext;

/**
 * Platform-superadmin view over every tenant (Business). The Business model is
 * the tenant root and carries no BusinessScope, but the per-tenant usage/plan
 * reads touch scoped models, so those are run under TenantContext::withoutScope.
 */
class TenantManagementService
{
    use HandlesEntityQueries;

    public function __construct(private readonly BusinessService $businesses) {}

    public function paginate(array $params = []): LengthAwarePaginator
    {
        $query = Business::query()->with('manager');

        if (! empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('business_code', 'like', "%{$search}%");
            });
        }

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $this->applySort($query, $params, ['name', 'created_at', 'status']);

        $page = $query->paginate($this->resolvePerPage($params));

        $this->attachCurrentSubscription($page->getCollection()->all());

        return $page;
    }

    /**
     * @return array{business: Business, statistics: array, subscription: ?Subscription, invoices: Collection}
     */
    public function detail(int $id): array
    {
        $business = Business::with('manager')->findOrFail($id);

        return TenantContext::withoutScope(fn () => [
            'business' => $business,
            'statistics' => $this->businesses->statistics($id),
            'subscription' => $this->currentSubscription($id),
            'invoices' => SubscriptionInvoice::where('business_id', $id)
                ->latest('paid_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function suspend(int $id): Business
    {
        return $this->setStatus($id, BusinessStatus::Suspended->value);
    }

    public function activate(int $id): Business
    {
        return $this->setStatus($id, BusinessStatus::Active->value);
    }

    private function setStatus(int $id, string $status): Business
    {
        $business = Business::findOrFail($id);
        $business->update(['status' => $status]);

        return $business->load('manager');
    }

    /**
     * Eager-load each business's active subscription in a single query and
     * hang it off the model as a `currentSubscription` relation.
     *
     * @param  array<int, Business>  $businesses
     */
    private function attachCurrentSubscription(array $businesses): void
    {
        if ($businesses === []) {
            return;
        }

        $ids = array_map(static fn (Business $b) => $b->id, $businesses);

        $subscriptions = TenantContext::withoutScope(
            fn () => Subscription::with('package')
                ->whereIn('business_id', $ids)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->get()
                ->keyBy('business_id')
        );

        foreach ($businesses as $business) {
            $business->setRelation('currentSubscription', $subscriptions->get($business->id));
        }
    }

    private function currentSubscription(int $businessId): ?Subscription
    {
        return Subscription::where('business_id', $businessId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->with('package')
            ->latest('started_at')
            ->first();
    }
}
