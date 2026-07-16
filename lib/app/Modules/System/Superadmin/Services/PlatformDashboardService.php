<?php

namespace App\Modules\System\Superadmin\Services;

use App\Modules\System\Subscription\Models\Subscription;
use Illuminate\Support\Facades\DB;

/**
 * Platform-wide metrics for the superadmin dashboard. All aggregates go through
 * the query builder (DB::table), which is not subject to Eloquent's BusinessScope,
 * so they naturally span every tenant.
 */
class PlatformDashboardService
{
    public function summary(): array
    {
        return [
            'tenants' => $this->tenantCounts(),
            'subscriptions' => $this->subscriptionCounts(),
            'revenue' => $this->revenue(),
            'plans' => $this->planDistribution(),
            'tenants_by_month' => $this->tenantsByMonth(6),
        ];
    }

    private function tenantCounts(): array
    {
        $byStatus = DB::table('sys_business')
            ->whereNull('deleted_at')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $byStatus->sum(),
            'active' => (int) ($byStatus['active'] ?? 0),
            'suspended' => (int) ($byStatus['suspended'] ?? 0),
            'inactive' => (int) ($byStatus['inactive'] ?? 0),
            'new_this_month' => (int) DB::table('sys_business')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    private function subscriptionCounts(): array
    {
        $active = DB::table('sys_subscriptions')->where('status', Subscription::STATUS_ACTIVE);

        return [
            'active_total' => (int) (clone $active)->count(),
            'trial' => (int) (clone $active)->where('price', 0)->count(),
            'paid' => (int) (clone $active)->where('price', '>', 0)->count(),
            'expired' => (int) DB::table('sys_subscriptions')
                ->where('status', Subscription::STATUS_EXPIRED)
                ->count(),
        ];
    }

    private function revenue(): array
    {
        // Monthly recurring revenue: yearly plans are normalised to a monthly figure.
        $mrr = (float) DB::table('sys_subscriptions')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('price', '>', 0)
            ->selectRaw("sum(case when billing_cycle = 'year' then price / 12 else price end) as mrr")
            ->value('mrr');

        $invoicesPaid = (float) DB::table('sys_subscription_invoices')
            ->where('status', 'paid')
            ->sum('amount');

        return [
            'mrr' => round($mrr, 2),
            'invoices_paid_total' => round($invoicesPaid, 2),
        ];
    }

    /**
     * @return array<int, array{package_id: int, package_code: ?string, name: ?string, active_subscriptions: int}>
     */
    private function planDistribution(): array
    {
        return DB::table('sys_subscriptions as s')
            ->join('sys_packages as p', 'p.id', '=', 's.package_id')
            ->where('s.status', Subscription::STATUS_ACTIVE)
            ->groupBy('p.id', 'p.package_code', 'p.name')
            ->select('p.id as package_id', 'p.package_code', 'p.name', DB::raw('count(*) as active_subscriptions'))
            ->orderByDesc('active_subscriptions')
            ->get()
            ->map(fn ($row) => [
                'package_id' => (int) $row->package_id,
                'package_code' => $row->package_code,
                'name' => $row->name,
                'active_subscriptions' => (int) $row->active_subscriptions,
            ])
            ->all();
    }

    /**
     * New-tenant counts for the last $months calendar months, oldest first.
     * Computed month-by-month in PHP to stay portable across sqlite/mysql.
     *
     * @return array<int, array{month: string, count: int}>
     */
    private function tenantsByMonth(int $months): array
    {
        $series = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = (clone $start)->endOfMonth();

            $series[] = [
                'month' => $start->format('Y-m'),
                'count' => (int) DB::table('sys_business')
                    ->whereNull('deleted_at')
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
        }

        return $series;
    }
}
