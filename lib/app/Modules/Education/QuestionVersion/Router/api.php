<?php

use App\Modules\Education\QuestionVersion\Http\Controllers\QuestionVersionController;
use Illuminate\Support\Facades\Route;

// question.md §IV "Version câu hỏi": read-only version history, reusing the question read code.
Route::prefix('question/version')->middleware('auth.tera')->group(function () {

    Route::get('/list/{questionId}', [QuestionVersionController::class, 'list'])->middleware('permission:question.view');
    Route::get('/detail/{id}', [QuestionVersionController::class, 'detail'])->middleware('permission:question.view');

});
