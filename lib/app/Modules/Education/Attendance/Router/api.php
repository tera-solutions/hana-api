<?php

use App\Modules\Education\Attendance\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

// Per-student attendance for class sessions (class-session.md §13, §15).
Route::prefix('attendance')->middleware('auth.tera')->group(function () {

    Route::get('/list', [AttendanceController::class, 'list'])->middleware('permission:attendance.list');
    Route::post('/create', [AttendanceController::class, 'create'])->middleware('permission:attendance.create');
    Route::put('/update/{id}', [AttendanceController::class, 'update'])->middleware('permission:attendance.update');

});
