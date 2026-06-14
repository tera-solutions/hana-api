<?php

use App\Modules\Education\Enrollment\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

// Enrollment lifecycle (enrollment.md §13).
Route::prefix('enrollment')->middleware('auth.tera')->group(function () {

    Route::get('/list', [EnrollmentController::class, 'list'])->middleware('permission:enrollment.list');
    Route::get('/detail/{id}', [EnrollmentController::class, 'detail'])->middleware('permission:enrollment.view');

    Route::post('/create', [EnrollmentController::class, 'create'])->middleware('permission:enrollment.create');
    Route::put('/update/{id}', [EnrollmentController::class, 'update'])->middleware('permission:enrollment.update');

    Route::post('/suspend/{id}', [EnrollmentController::class, 'suspend'])->middleware('permission:enrollment.suspend');
    Route::post('/transfer/{id}', [EnrollmentController::class, 'transfer'])->middleware('permission:enrollment.transfer');
    Route::post('/refund/{id}', [EnrollmentController::class, 'refund'])->middleware('permission:enrollment.refund');
    Route::post('/cancel/{id}', [EnrollmentController::class, 'cancel'])->middleware('permission:enrollment.cancel');

});
