<?php

use App\Modules\Education\Course\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('course')->middleware('auth.tera')->group(function () {

    Route::get('/list', [CourseController::class, 'list'])->middleware('permission:course.list');
    Route::get('/detail/{id}', [CourseController::class, 'detail'])->middleware('permission:course.view');

    Route::post('/create', [CourseController::class, 'create'])->middleware('permission:course.create');
    Route::put('/update/{id}', [CourseController::class, 'update'])->middleware('permission:course.update');

    Route::post('/suspend/{id}', [CourseController::class, 'suspend'])->middleware('permission:course.suspend');
    Route::post('/restore/{id}', [CourseController::class, 'restore'])->middleware('permission:course.restore');

    // Report endpoints (course.md §8).
    Route::get('/statistics/{id}', [CourseController::class, 'statistics'])->middleware('permission:course.view');
    Route::get('/financial-summary/{id}', [CourseController::class, 'financialSummary'])->middleware('permission:course.view');
    Route::get('/rating-summary/{id}', [CourseController::class, 'ratingSummary'])->middleware('permission:course.view');

});
