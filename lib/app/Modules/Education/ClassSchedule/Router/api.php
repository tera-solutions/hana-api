<?php

use App\Modules\Education\ClassSchedule\Http\Controllers\ClassScheduleController;
use Illuminate\Support\Facades\Route;

// Schedule sub-resource — nested under a class (reuses parent class.* permissions).
Route::prefix('class-room')->middleware('auth.tera')->group(function () {

    Route::get('/{classId}/schedule/list', [ClassScheduleController::class, 'list'])->middleware('permission:class.view');
    Route::post('/{classId}/schedule/create', [ClassScheduleController::class, 'create'])->middleware('permission:class.update');

});

// Individual schedule management.
Route::prefix('class-schedule')->middleware('auth.tera')->group(function () {

    Route::get('/detail/{id}', [ClassScheduleController::class, 'detail'])->middleware('permission:class.view');
    Route::put('/update/{id}', [ClassScheduleController::class, 'update'])->middleware('permission:class.update');
    Route::delete('/delete/{id}', [ClassScheduleController::class, 'delete'])->middleware('permission:class.update');

});
