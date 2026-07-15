<?php

use App\Modules\Education\LessonPlanLesson\Http\Controllers\LessonPlanLessonActivityController;
use App\Modules\Education\LessonPlanLesson\Http\Controllers\LessonPlanLessonController;
use Illuminate\Support\Facades\Route;

// Lesson templates are sub-resources of a lesson plan and reuse its permission codes.
Route::prefix('lesson-plan')->middleware('auth.tera')->group(function () {

    Route::post('/lesson/create/{planId}', [LessonPlanLessonController::class, 'store'])->middleware('permission:lesson_plan.update');
    Route::put('/lesson/update/{id}', [LessonPlanLessonController::class, 'update'])->middleware('permission:lesson_plan.update');
    Route::delete('/lesson/delete/{id}', [LessonPlanLessonController::class, 'destroy'])->middleware('permission:lesson_plan.update');
    Route::post('/lesson/reorder/{planId}', [LessonPlanLessonController::class, 'reorder'])->middleware('permission:lesson_plan.update');

    // Activities are sub-resources of a lesson-plan-lesson; same permission codes.
    Route::get('/lesson-activity/list', [LessonPlanLessonActivityController::class, 'list'])->middleware('permission:lesson_plan.view');
    Route::get('/lesson-activity/detail/{id}', [LessonPlanLessonActivityController::class, 'detail'])->middleware('permission:lesson_plan.view');
    Route::post('/lesson-activity/create', [LessonPlanLessonActivityController::class, 'create'])->middleware('permission:lesson_plan.update');
    Route::put('/lesson-activity/update/{id}', [LessonPlanLessonActivityController::class, 'update'])->middleware('permission:lesson_plan.update');
    Route::delete('/lesson-activity/delete/{id}', [LessonPlanLessonActivityController::class, 'delete'])->middleware('permission:lesson_plan.update');

});
