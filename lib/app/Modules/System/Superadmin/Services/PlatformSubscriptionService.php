<?php

namespace App\Modules\System\Superadmin\Services;

use App\Modules\System\Package\Models\Package;
use App\Modules\System\Subscription\Models\Subscription;
use App\Modules\System\Subscription\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;
use Package\Tenancy\TenantContext;
use RuntimeException;

/**
 * Cross-tenant subscription control for the platform superadmin: assign/switch a
 * plan, extend, or cancel any tenant's subscription. Billing is manual for now
 * (invoices are stamped paid). Every operation runs under withoutScope so it
 * targets the given business rather than the acting superadmin's own tenant.
 */
class PlatformSubscriptionService
{
    public function current(int $businessId): ?Subscription
    {
        return TenantContext::withoutScope(fn () => $this->activeSubscription($businessId));
    }

    /**
     * Start or switch a tenant onto a package. Any existing active subscription
     * is cancelled and a paid invoice is recorded for the change.
     */
    public function assign(int $businessId, array $data): Subscription
    {
        return TenantContext::withoutScope(fn () => DB::transaction(function () use ($businessId, $data) {
            $package = Package::findOrFail($data['package_id']);

            Subscription::where('business_id', $businessId)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_CANCELLED]);

            $cycle = $data['billing_cycle'] ?? $package->billing_cycle;
            $amount = $data['amount'] ?? $package->price;

            $subscription = Subscription::create([
                'business_id' => $businessId,
                'package_id' => $package->id,
                'price' => $amount,
                'billing_cycle' => $cycle,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => Subscription::STATUS_ACTIVE,
                'started_at' => now()->toDateString(),
                'expires_at' => $cycle === 'year'
                    ? now()->addYear()->toDateString()
                    : now()->addMonth()->toDateString(),
            ]);

            SubscriptionInvoice::create([
                'subscription_id' => $subscription->id,
                'business_id' => $businessId,
                'code' => $this->generateInvoiceCode(),
                'package_name' => $package->name,
                'billing_cycle' => $cycle,
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $subscription->load('package');
        }));
    }

    /**
     * Push the tenant's active subscription expiry out by $months, counting from
     * the later of today or the current expiry (so unused time is not lost).
     */
    public function extend(int $businessId, int $months): Subscription
    {
        return TenantContext::withoutScope(function () use ($businessId, $months) {
            $subscription = $this->activeSubscription($businessId);

            if (! $subscription) {
                throw new RuntimeException('Tenant chưa có gói đang hoạt động để gia hạn. Hãy gán gói trước.');
            }

            $base = $subscription->expires_at && ! $subscription->expires_at->isPast()
                ? $subscription->expires_at->copy()
                : now();

            $subscription->update(['expires_at' => $base->addMonths($months)->toDateString()]);

            return $subscription->load('package');
        });
    }

    public function cancel(int $businessId): Subscription
    {
        return TenantContext::withoutScope(function () use ($businessId) {
            $subscription = $this->activeSubscription($businessId);

            if (! $subscription) {
                throw new RuntimeException('Tenant chưa có gói đang hoạt động để hủy.');
            }

            $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

            return $subscription->load('package');
        });
    }

    private function activeSubscription(int $businessId): ?Subscription
    {
        return Subscription::where('business_id', $businessId)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->with('package')
            ->latest('started_at')
            ->first();
    }

    private function generateInvoiceCode(): string
    {
        $count = SubscriptionInvoice::count() + 1;

        return 'INV-'.now()->format('y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
