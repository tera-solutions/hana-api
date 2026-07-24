<?php

use App\Modules\Education\Lesson\Http\Controllers\LessonActivityController;
use App\Modules\Education\Lesson\Http\Controllers\LessonController;
use Illuminate\Support\Facades\Route;

Route::prefix('lesson')->middleware('auth.tera')->group(function () {

    Route::get('/list', [LessonController::class, 'list'])->middleware('permission:lesson.list');
    Route::get('/detail/{id}', [LessonController::class, 'detail'])->middleware('permission:lesson.view');

    Route::put('/update/{id}', [LessonController::class, 'update'])->middleware('permission:lesson.update');
    Route::post('/change-plan/{id}', [LessonController::class, 'changePlan'])->middleware('permission:lesson.update');
    Route::post('/reschedule/{id}', [LessonController::class, 'reschedule'])->middleware('permission:lesson.reschedule');
    Route::post('/cancel/{id}', [LessonController::class, 'cancel'])->middleware('permission:lesson.cancel');

    Route::post('/complete/{id}', [LessonController::class, 'complete'])->middleware('permission:lesson.update');
    Route::post('/lock/{id}', [LessonController::class, 'lock'])->middleware('permission:lesson.update');
    Route::post('/unlock/{id}', [LessonController::class, 'unlock'])->middleware('permission:lesson.unlock');

});

Route::prefix('lesson-activity')->middleware('auth.tera')->group(function () {

    Route::put('/update/{id}', [LessonActivityController::class, 'update'])->middleware('permission:lesson.update');

});
