<?php

use App\Modules\Education\Material\Http\Controllers\MaterialCategoryController;
use App\Modules\Education\Material\Http\Controllers\MaterialController;
use Illuminate\Support\Facades\Route;

Route::prefix('material')->middleware('auth.tera')->group(function () {

    Route::get('/list', [MaterialController::class, 'list'])->middleware('permission:material.list');
    Route::get('/detail/{id}', [MaterialController::class, 'detail'])->middleware('permission:material.view');

    Route::post('/create', [MaterialController::class, 'create'])->middleware('permission:material.create');
    Route::put('/update/{id}', [MaterialController::class, 'update'])->middleware('permission:material.update');

    // Versioning (material.md §8).
    Route::post('/upload/{id}', [MaterialController::class, 'upload'])->middleware('permission:material.update');
    Route::post('/rollback/{id}', [MaterialController::class, 'rollback'])->middleware('permission:material.update');

    Route::post('/publish/{id}', [MaterialController::class, 'publish'])->middleware('permission:material.update');
    Route::delete('/delete/{id}', [MaterialController::class, 'delete'])->middleware('permission:material.delete');

    // Linking (material.md §9).
    Route::post('/attach/{id}', [MaterialController::class, 'attach'])->middleware('permission:material.update');
    Route::delete('/mapping/delete/{id}', [MaterialController::class, 'detach'])->middleware('permission:material.update');
    Route::get('/mappings/{id}', [MaterialController::class, 'mappings'])->middleware('permission:material.view');

});

// Document-library taxonomy (material.md §6).
Route::prefix('material-category')->middleware('auth.tera')->group(function () {

    Route::get('/list', [MaterialCategoryController::class, 'list'])->middleware('permission:material.manage');
    Route::post('/create', [MaterialCategoryController::class, 'create'])->middleware('permission:material.manage');
    Route::put('/update/{id}', [MaterialCategoryController::class, 'update'])->middleware('permission:material.manage');
    Route::delete('/delete/{id}', [MaterialCategoryController::class, 'delete'])->middleware('permission:material.manage');

});
