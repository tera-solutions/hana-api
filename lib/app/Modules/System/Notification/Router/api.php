<?php

use App\Modules\System\Notification\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notification')->middleware('auth.tera')->group(function () {
    Route::get('/list', [NotificationController::class, 'list']);
    Route::get('/detail/{id}', [NotificationController::class, 'detail']);
    Route::post('/create', [NotificationController::class, 'create']);
    Route::put('/update/{id}', [NotificationController::class, 'update']);
    Route::delete('/delete/{id}', [NotificationController::class, 'delete']);
});
