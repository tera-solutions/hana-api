<?php

namespace App\Modules\System\ActivityLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\ActivityLog\Http\Resources\ActivityLogResource;
use App\Modules\System\ActivityLog\Services\ActivityLogService;
use Illuminate\Http\Request;

/**
 * @group System - Activity Log
 *
 * Read-only system audit trail (spec 028). Logs are immutable — there is no
 * create/update/delete endpoint (BR-02, BR-03, BR-06).
 *
 * @authenticated
 */
class ActivityLogController extends Controller
{
    /**
     * List activity logs
     *
     * @queryParam search string Match description or entity_id. Example: Student
     * @queryParam module string Filter by module. Example: education
     * @queryParam entity string Filter by entity. Example: Student
     * @queryParam action string Filter by action. Example: update
     * @queryParam status string Filter by status. Example: success
     * @queryParam user_id integer Filter by actor. Example: 1
     * @queryParam period string Relative range: today, 7d, 30d. Example: 7d
     * @queryParam date_from date Range start (Y-m-d). Example: 2026-06-01
     * @queryParam date_to date Range end (Y-m-d). Example: 2026-06-18
     */
    public function list(Request $request, ActivityLogService $service)
    {
        return $this->respondPaginated($service->paginate($request->all()), ActivityLogResource::class);
    }

    /**
     * Activity log detail
     *
     * @urlParam id integer required The log ID. Example: 1
     */
    public function detail($id, ActivityLogService $service)
    {
        return $this->respondSuccess(new ActivityLogResource($service->find($id)));
    }

    /**
     * Activity log statistics
     *
     * Counters by module/action plus top users and failed logins (spec 028 §XII).
     */
    public function statistics(Request $request, ActivityLogService $service)
    {
        return $this->respondSuccess($service->statistics($request->all()));
    }

    /**
     * Export activity logs
     */
    public function export(Request $request, ActivityLogService $service)
    {
        return $this->respondSuccess($service->export($request->all()));
    }
}
