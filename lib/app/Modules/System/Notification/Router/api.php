<?php

use App\Modules\System\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notification')->middleware('auth.tera')->group(function () {
    Route::get('/list', [NotificationController::class, 'list'])->middleware('permission:notification.list');
    Route::get('/detail/{id}', [NotificationController::class, 'detail'])->middleware('permission:notification.view');
    Route::post('/read/{id}', [NotificationController::class, 'read'])->middleware('permission:notification.read');

    // Broadcasting notifications is the premium "messaging" feature; receiving
    // (list/detail) stays available to every plan.
    Route::post('/create', [NotificationController::class, 'create'])->middleware(['permission:notification.create', 'subscription.feature:messaging']);
    Route::put('/update/{id}', [NotificationController::class, 'update'])->middleware(['permission:notification.update', 'subscription.feature:messaging']);
    Route::delete('/delete/{id}', [NotificationController::class, 'delete'])->middleware(['permission:notification.delete', 'subscription.feature:messaging']);
});
