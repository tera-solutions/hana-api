<?php

use App\Modules\Education\Level\Http\Controllers\LevelController;
use Illuminate\Support\Facades\Route;

Route::prefix('level')->middleware('auth.tera')->group(function () {

    Route::get('/list', [LevelController::class, 'list'])->middleware('permission:level.list');
    Route::get('/detail/{id}', [LevelController::class, 'detail'])->middleware('permission:level.view');

    Route::post('/create', [LevelController::class, 'create'])->middleware('permission:level.create');
    Route::put('/update/{id}', [LevelController::class, 'update'])->middleware('permission:level.update');

    Route::post('/suspend/{id}', [LevelController::class, 'suspend'])->middleware('permission:level.suspend');
    Route::post('/restore/{id}', [LevelController::class, 'restore'])->middleware('permission:level.restore');

});
