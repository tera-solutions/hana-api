<?php

use App\Modules\Education\ExamVersion\Http\Controllers\ExamVersionController;
use Illuminate\Support\Facades\Route;

// exam.md §IV "Version đề thi": the version lineage of an exam.
Route::prefix('exam/version')->middleware('auth.tera')->group(function () {

    Route::get('/list/{examId}', [ExamVersionController::class, 'list'])->middleware('permission:exam.view');
    Route::get('/detail/{id}', [ExamVersionController::class, 'detail'])->middleware('permission:exam.view');

});
