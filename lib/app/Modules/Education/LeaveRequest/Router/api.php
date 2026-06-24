<?php

use App\Modules\Education\LeaveRequest\Http\Controllers\LeaveRequestController;
use Illuminate\Support\Facades\Route;

// Student / teacher leave requests + make-up scheduling (leave-request.md §XV).
Route::prefix('leave')->middleware('auth.tera')->group(function () {

    Route::get('/list', [LeaveRequestController::class, 'list'])->middleware('permission:leave.list');
    Route::get('/detail/{id}', [LeaveRequestController::class, 'detail'])->middleware('permission:leave.view');

    Route::post('/create', [LeaveRequestController::class, 'create'])->middleware('permission:leave.create');
    Route::put('/update/{id}', [LeaveRequestController::class, 'update'])->middleware('permission:leave.update');

    Route::post('/approve/{id}', [LeaveRequestController::class, 'approve'])->middleware('permission:leave.approve');
    Route::post('/reject/{id}', [LeaveRequestController::class, 'reject'])->middleware('permission:leave.reject');
    Route::post('/cancel/{id}', [LeaveRequestController::class, 'cancel'])->middleware('permission:leave.cancel');

    Route::post('/makeup/schedule/{makeupId}', [LeaveRequestController::class, 'scheduleMakeup'])->middleware('permission:leave.makeup');

});
