<?php

use App\Modules\Education\LessonPlan\Http\Controllers\LessonPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('lesson-plan')->middleware('auth.tera')->group(function () {

    Route::get('/list', [LessonPlanController::class, 'list'])->middleware('permission:lesson_plan.list');
    Route::get('/summary', [LessonPlanController::class, 'summary'])->middleware('permission:lesson_plan.list');
    Route::get('/detail/{id}', [LessonPlanController::class, 'detail'])->middleware('permission:lesson_plan.view');

    Route::post('/create', [LessonPlanController::class, 'create'])->middleware('permission:lesson_plan.create');
    Route::put('/update/{id}', [LessonPlanController::class, 'update'])->middleware('permission:lesson_plan.update');

    Route::post('/clone/{id}', [LessonPlanController::class, 'clone'])->middleware('permission:lesson_plan.clone');
    Route::post('/publish/{id}', [LessonPlanController::class, 'publish'])->middleware('permission:lesson_plan.publish');
    Route::post('/archive/{id}', [LessonPlanController::class, 'archive'])->middleware('permission:lesson_plan.update');
    Route::post('/restore/{id}', [LessonPlanController::class, 'restore'])->middleware('permission:lesson_plan.update');

});
