<?php

namespace App\Modules\System\Superadmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Superadmin\Services\PlatformDashboardService;

/**
 * @group System - Superadmin / Dashboard
 *
 * Platform-wide metrics across all tenants. Restricted to superadmins.
 *
 * @authenticated
 */
class DashboardController extends Controller
{
    /**
     * Platform metrics
     *
     * Tenant counts by status, subscription mix (trial/paid/expired), monthly
     * recurring revenue, active-plan distribution and new-tenant growth.
     *
     * @response 200 {
     *   "success": true,
     *   "msg": "Thao tác thành công",
     *   "data": {
     *     "tenants": {"total": 12, "active": 10, "suspended": 1, "inactive": 1, "new_this_month": 3},
     *     "subscriptions": {"active_total": 9, "trial": 4, "paid": 5, "expired": 2},
     *     "revenue": {"mrr": 1495000, "invoices_paid_total": 4485000},
     *     "plans": [{"package_id": 2, "package_code": "PKG-BASIC", "name": "Gói Cơ bản", "active_subscriptions": 5}],
     *     "tenants_by_month": [{"month": "2026-02", "count": 2}]
     *   },
     *   "code": 200,
     *   "errors": null
     * }
     */
    public function index(PlatformDashboardService $service)
    {
        return $this->respondSuccess($service->summary());
    }
}
