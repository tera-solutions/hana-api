<?php

use App\Modules\Education\Evaluation\Http\Controllers\EvaluationController;
use Illuminate\Support\Facades\Route;

// Teacher / student / parent evaluations (evaluation.md §IX).
Route::prefix('evaluation')->middleware('auth.tera')->group(function () {

    Route::get('/list', [EvaluationController::class, 'list'])->middleware('permission:evaluation.list');
    Route::get('/detail/{id}', [EvaluationController::class, 'detail'])->middleware('permission:evaluation.view');

    Route::post('/create', [EvaluationController::class, 'create'])->middleware('permission:evaluation.create');
    Route::put('/update/{id}', [EvaluationController::class, 'update'])->middleware('permission:evaluation.update');
    Route::delete('/delete/{id}', [EvaluationController::class, 'delete'])->middleware('permission:evaluation.delete');

    // Workflow: author submits; academic manager approves / rejects / locks.
    Route::post('/submit/{id}', [EvaluationController::class, 'submit'])->middleware('permission:evaluation.update');
    Route::post('/approve/{id}', [EvaluationController::class, 'approve'])->middleware('permission:evaluation.approve');
    Route::post('/reject/{id}', [EvaluationController::class, 'reject'])->middleware('permission:evaluation.approve');
    Route::post('/lock/{id}', [EvaluationController::class, 'lock'])->middleware('permission:evaluation.approve');

});
