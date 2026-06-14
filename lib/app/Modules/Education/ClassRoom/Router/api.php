<?php

use App\Modules\Education\ClassRoom\Http\Controllers\ClassController;
use Illuminate\Support\Facades\Route;

// Class CRUD (spec §11).
Route::prefix('class-room')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ClassController::class, 'list'])->middleware('permission:class.list');
    Route::get('/detail/{id}', [ClassController::class, 'detail'])->middleware('permission:class.view');

    Route::post('/create', [ClassController::class, 'create'])->middleware('permission:class.create');
    Route::put('/update/{id}', [ClassController::class, 'update'])->middleware('permission:class.update');

    Route::post('/suspend/{id}', [ClassController::class, 'suspend'])->middleware('permission:class.suspend');
    Route::post('/restore/{id}', [ClassController::class, 'restore'])->middleware('permission:class.restore');

});
