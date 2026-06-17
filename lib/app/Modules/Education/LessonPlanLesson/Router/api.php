<?php

use App\Modules\Education\LessonPlanLesson\Http\Controllers\LessonPlanLessonController;
use Illuminate\Support\Facades\Route;

// Lesson templates are sub-resources of a lesson plan and reuse its permission codes.
Route::prefix('lesson-plan')->middleware('auth.tera')->group(function () {

    Route::post('/lesson/create/{planId}', [LessonPlanLessonController::class, 'store'])->middleware('permission:lesson_plan.update');
    Route::put('/lesson/update/{id}', [LessonPlanLessonController::class, 'update'])->middleware('permission:lesson_plan.update');
    Route::delete('/lesson/delete/{id}', [LessonPlanLessonController::class, 'destroy'])->middleware('permission:lesson_plan.update');
    Route::post('/lesson/reorder/{planId}', [LessonPlanLessonController::class, 'reorder'])->middleware('permission:lesson_plan.update');

});
