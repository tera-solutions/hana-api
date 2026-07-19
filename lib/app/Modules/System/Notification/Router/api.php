<?php

use App\Modules\System\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notification')->middleware('auth.tera')->group(function () {
    Route::get('/list', [NotificationController::class, 'list']);
    Route::get('/detail/{id}', [NotificationController::class, 'detail']);
    Route::post('/read/{id}', [NotificationController::class, 'read']);

    // Broadcasting notifications is the premium "messaging" feature; receiving
    // (list/detail) stays available to every plan.
    Route::post('/create', [NotificationController::class, 'create'])->middleware('subscription.feature:messaging');
    Route::put('/update/{id}', [NotificationController::class, 'update'])->middleware('subscription.feature:messaging');
    Route::delete('/delete/{id}', [NotificationController::class, 'delete'])->middleware('subscription.feature:messaging');
});
