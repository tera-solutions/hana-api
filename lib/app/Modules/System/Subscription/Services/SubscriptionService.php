<?php

namespace App\Modules\System\Subscription\Services;

use App\Modules\System\Package\Models\Package;
use App\Modules\System\Subscription\Models\Subscription;
use App\Modules\System\Subscription\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Package\Database\Concerns\HandlesEntityQueries;

class SubscriptionService
{
    use HandlesEntityQueries;

    /**
     * The acting business's current (latest active) subscription.
     */
    public function current(): ?Subscription
    {
        return Subscription::where('business_id', $this->actingBusinessId())
            ->where('status', Subscription::STATUS_ACTIVE)
            ->with('package')
            ->latest('started_at')
            ->first();
    }

    /**
     * Switch (or start) the acting business's subscription to another package.
     * Marks any existing active subscription as cancelled and creates a paid
     * invoice row for the change, mirroring the mockup's billing-history table.
     */
    public function upgrade(array $data): Subscription
    {
        return DB::transaction(function () use ($data) {
            $businessId = $this->actingBusinessId();
            $package = Package::findOrFail($data['package_id']);

            Subscription::where('business_id', $businessId)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_CANCELLED]);

            $cycle = $data['billing_cycle'] ?? $package->billing_cycle;
            $subscription = Subscription::create([
                'business_id' => $businessId,
                'package_id' => $package->id,
                'price' => $package->price,
                'billing_cycle' => $cycle,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => Subscription::STATUS_ACTIVE,
                'started_at' => now()->toDateString(),
                'expires_at' => $cycle === 'year'
                    ? now()->addYear()->toDateString()
                    : now()->addMonth()->toDateString(),
            ]);

            $this->recordInvoice($subscription, $package, $businessId, $cycle, $data['payment_method'] ?? null);

            return $subscription->load('package');
        });
    }

    /**
     * Log a billing-history row for a subscription — paid upgrades and the
     * free trial started at signup alike, so "Lịch sử hóa đơn gói" is never
     * empty for a business that has an active subscription.
     */
    public function recordInvoice(
        Subscription $subscription,
        Package $package,
        int $businessId,
        string $cycle,
        ?string $paymentMethod,
    ): SubscriptionInvoice {
        return SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'business_id' => $businessId,
            'code' => $this->generateInvoiceCode(),
            'package_name' => $package->name,
            'billing_cycle' => $cycle,
            'amount' => $package->price,
            'payment_method' => $paymentMethod,
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function invoices(array $params = [])
    {
        $query = SubscriptionInvoice::where('business_id', $this->actingBusinessId());

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $this->applySort($query, $params, ['code', 'amount', 'created_at']);

        return $query->latest('paid_at')->paginate($this->resolvePerPage($params));
    }

    private function generateInvoiceCode(): string
    {
        $count = SubscriptionInvoice::count() + 1;

        return 'INV-'.now()->format('y').'-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function actingBusinessId(): ?int
    {
        $user = Auth::guard('api')->user() ?? Auth::user();

        return $user?->business_id;
    }
}
