<?php

use App\Modules\System\ActivityLog\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

// Read-only audit trail (activity-log.md §IX). Immutable: no write endpoints.
Route::prefix('activity-log')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ActivityLogController::class, 'list'])->middleware('permission:activity_log.list');
    Route::get('/statistics', [ActivityLogController::class, 'statistics'])->middleware('permission:activity_log.view');
    Route::get('/export', [ActivityLogController::class, 'export'])->middleware('permission:activity_log.export');
    Route::get('/detail/{id}', [ActivityLogController::class, 'detail'])->middleware('permission:activity_log.view');

});
