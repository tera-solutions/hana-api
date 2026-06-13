<?php

use App\Modules\Education\ClassRoom\Http\Controllers\ClassController;
use App\Modules\Education\ClassRoom\Http\Controllers\ClassScheduleController;
use Illuminate\Support\Facades\Route;

// Class CRUD (spec §11).
Route::prefix('class-room')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ClassController::class, 'list'])->middleware('permission:class.list');
    Route::get('/detail/{id}', [ClassController::class, 'detail'])->middleware('permission:class.view');

    Route::post('/create', [ClassController::class, 'create'])->middleware('permission:class.create');
    Route::put('/update/{id}', [ClassController::class, 'update'])->middleware('permission:class.update');

    Route::post('/suspend/{id}', [ClassController::class, 'suspend'])->middleware('permission:class.suspend');
    Route::post('/restore/{id}', [ClassController::class, 'restore'])->middleware('permission:class.restore');

    // Schedule sub-resource — nested under a class.
    Route::get('/{classId}/schedule/list', [ClassScheduleController::class, 'list'])->middleware('permission:class.view');
    Route::post('/{classId}/schedule/create', [ClassScheduleController::class, 'create'])->middleware('permission:class.update');

});

// Individual schedule management (spec §11 class-schedules).
Route::prefix('class-schedule')->middleware('auth.tera')->group(function () {

    Route::get('/detail/{id}', [ClassScheduleController::class, 'detail'])->middleware('permission:class.view');
    Route::put('/update/{id}', [ClassScheduleController::class, 'update'])->middleware('permission:class.update');
    Route::delete('/delete/{id}', [ClassScheduleController::class, 'delete'])->middleware('permission:class.update');

});
