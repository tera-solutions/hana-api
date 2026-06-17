<?php

use App\Modules\Education\LessonPlanVersion\Http\Controllers\LessonPlanVersionController;
use Illuminate\Support\Facades\Route;

// lesson-plan.md §13: read-only version history, gated by the dedicated permission.
Route::prefix('lesson-plan/version')->middleware('auth.tera')->group(function () {

    Route::get('/list/{planId}', [LessonPlanVersionController::class, 'list'])->middleware('permission:lesson_plan.version');
    Route::get('/detail/{id}', [LessonPlanVersionController::class, 'detail'])->middleware('permission:lesson_plan.version');

});
