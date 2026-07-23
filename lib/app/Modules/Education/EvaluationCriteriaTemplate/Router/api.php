<?php

use App\Modules\Education\EvaluationCriteriaTemplate\Http\Controllers\EvaluationCriteriaTemplateController;
use Illuminate\Support\Facades\Route;

// Reusable evaluation rubrics — shared (admin) or private (self-defined) criteria lists.
Route::prefix('evaluation-criteria-template')->middleware('auth.tera')->group(function () {

    Route::get('/list', [EvaluationCriteriaTemplateController::class, 'list'])->middleware('permission:evaluation_criteria_template.list');
    Route::get('/detail/{id}', [EvaluationCriteriaTemplateController::class, 'detail'])->middleware('permission:evaluation_criteria_template.view');

    Route::post('/create', [EvaluationCriteriaTemplateController::class, 'create'])->middleware('permission:evaluation_criteria_template.create');
    Route::put('/update/{id}', [EvaluationCriteriaTemplateController::class, 'update'])->middleware('permission:evaluation_criteria_template.update');

    Route::post('/suspend/{id}', [EvaluationCriteriaTemplateController::class, 'suspend'])->middleware('permission:evaluation_criteria_template.suspend');
    Route::post('/restore/{id}', [EvaluationCriteriaTemplateController::class, 'restore'])->middleware('permission:evaluation_criteria_template.restore');
});
