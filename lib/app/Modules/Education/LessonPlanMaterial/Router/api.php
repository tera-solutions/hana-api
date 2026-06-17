<?php

use App\Modules\Education\LessonPlanMaterial\Http\Controllers\LessonPlanMaterialController;
use Illuminate\Support\Facades\Route;

// Materials are sub-resources of a lesson template and reuse the plan's permission codes.
Route::prefix('lesson-plan')->middleware('auth.tera')->group(function () {

    Route::post('/lesson/{id}/material/attach', [LessonPlanMaterialController::class, 'attach'])->middleware('permission:lesson_plan.update');
    Route::delete('/material/delete/{id}', [LessonPlanMaterialController::class, 'detach'])->middleware('permission:lesson_plan.update');

});
