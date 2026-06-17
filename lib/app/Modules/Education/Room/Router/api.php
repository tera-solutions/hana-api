<?php

use App\Modules\Education\Room\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('room')->middleware('auth.tera')->group(function () {

    Route::get('/list', [RoomController::class, 'list'])->middleware('permission:room.list');
    Route::get('/detail/{id}', [RoomController::class, 'detail'])->middleware('permission:room.view');

    Route::post('/create', [RoomController::class, 'create'])->middleware('permission:room.create');
    Route::put('/update/{id}', [RoomController::class, 'update'])->middleware('permission:room.update');

    Route::post('/suspend/{id}', [RoomController::class, 'suspend'])->middleware('permission:room.suspend');
    Route::post('/restore/{id}', [RoomController::class, 'restore'])->middleware('permission:room.restore');

    // room.md §11: schedule-conflict check.
    Route::get('/schedule/{id}', [RoomController::class, 'schedule'])->middleware('permission:room.view');

});
