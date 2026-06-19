<?php

use App\Modules\Education\Question\Http\Controllers\QuestionCategoryController;
use App\Modules\Education\Question\Http\Controllers\QuestionController;
use App\Modules\Education\Question\Http\Controllers\QuestionTagController;
use Illuminate\Support\Facades\Route;

// Question bank (question.md §IV, §VIII, §IX, §XI).
Route::prefix('question')->middleware('auth.tera')->group(function () {

    Route::get('/list', [QuestionController::class, 'list'])->middleware('permission:question.list');
    Route::get('/detail/{id}', [QuestionController::class, 'detail'])->middleware('permission:question.view');

    Route::post('/create', [QuestionController::class, 'create'])->middleware('permission:question.create');
    Route::put('/update/{id}', [QuestionController::class, 'update'])->middleware('permission:question.update');
    Route::post('/clone/{id}', [QuestionController::class, 'clone'])->middleware('permission:question.create');
    Route::delete('/delete/{id}', [QuestionController::class, 'delete'])->middleware('permission:question.delete');

    // Review workflow (question.md §IX).
    Route::post('/review/{id}', [QuestionController::class, 'review'])->middleware('permission:question.update');
    Route::post('/approve/{id}', [QuestionController::class, 'approve'])->middleware('permission:question.approve');
    Route::post('/activate/{id}', [QuestionController::class, 'activate'])->middleware('permission:question.approve');
    Route::post('/archive/{id}', [QuestionController::class, 'archive'])->middleware('permission:question.update');

    Route::post('/import', [QuestionController::class, 'import'])->middleware('permission:question.import');
    Route::post('/generate-exam', [QuestionController::class, 'generateExam'])->middleware('permission:question.generate_exam');

});

// Categories reuse the question codes (reads → view, writes → update).
Route::prefix('question-category')->middleware('auth.tera')->group(function () {

    Route::get('/list', [QuestionCategoryController::class, 'list'])->middleware('permission:question.view');
    Route::post('/create', [QuestionCategoryController::class, 'create'])->middleware('permission:question.update');
    Route::put('/update/{id}', [QuestionCategoryController::class, 'update'])->middleware('permission:question.update');
    Route::delete('/delete/{id}', [QuestionCategoryController::class, 'delete'])->middleware('permission:question.update');

});

// Tags reuse the question codes (reads → view, writes → update).
Route::prefix('question-tag')->middleware('auth.tera')->group(function () {

    Route::get('/list', [QuestionTagController::class, 'list'])->middleware('permission:question.view');
    Route::post('/create', [QuestionTagController::class, 'create'])->middleware('permission:question.update');
    Route::put('/update/{id}', [QuestionTagController::class, 'update'])->middleware('permission:question.update');
    Route::delete('/delete/{id}', [QuestionTagController::class, 'delete'])->middleware('permission:question.update');

});
