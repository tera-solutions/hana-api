<?php

use App\Modules\Education\Exam\Http\Controllers\ExamController;
use App\Modules\Education\Exam\Http\Controllers\ExamResultController;
use App\Modules\Education\Exam\Http\Controllers\ExamSessionController;
use Illuminate\Support\Facades\Route;

// Exam bank + questions (exam.md §VI, §VII).
Route::prefix('exam')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ExamController::class, 'list'])->middleware('permission:exam.list');
    Route::get('/detail/{id}', [ExamController::class, 'detail'])->middleware('permission:exam.view');

    Route::post('/create', [ExamController::class, 'create'])->middleware('permission:exam.create');
    Route::put('/update/{id}', [ExamController::class, 'update'])->middleware('permission:exam.update');
    Route::post('/clone/{id}', [ExamController::class, 'clone'])->middleware('permission:exam.create');
    Route::delete('/delete/{id}', [ExamController::class, 'delete'])->middleware('permission:exam.delete');

    // Questions reuse the exam's codes (writes → update, delete → delete).
    Route::post('/question/create/{id}', [ExamController::class, 'addQuestion'])->middleware('permission:exam.update');
    Route::put('/question/update/{id}', [ExamController::class, 'updateQuestion'])->middleware('permission:exam.update');
    Route::delete('/question/delete/{id}', [ExamController::class, 'deleteQuestion'])->middleware('permission:exam.delete');

});

// Exam sessions + registration (exam.md §VIII, §IX).
Route::prefix('exam-session')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ExamSessionController::class, 'list'])->middleware('permission:exam.view');
    Route::get('/detail/{id}', [ExamSessionController::class, 'detail'])->middleware('permission:exam.view');

    Route::post('/create', [ExamSessionController::class, 'create'])->middleware('permission:exam.schedule');
    Route::put('/update/{id}', [ExamSessionController::class, 'update'])->middleware('permission:exam.schedule');
    Route::delete('/delete/{id}', [ExamSessionController::class, 'delete'])->middleware('permission:exam.schedule');

    Route::post('/register/class/{id}', [ExamSessionController::class, 'registerByClass'])->middleware('permission:exam.schedule');
    Route::post('/register/student/{id}', [ExamSessionController::class, 'registerByStudent'])->middleware('permission:exam.schedule');

});

// Exam results — grade / publish / promote (exam.md §XI–§XIII). {id} = registration ID.
Route::prefix('exam-result')->middleware('auth.tera')->group(function () {

    Route::post('/grade/{id}', [ExamResultController::class, 'grade'])->middleware('permission:exam.grade');
    Route::post('/publish/{id}', [ExamResultController::class, 'publish'])->middleware('permission:exam.publish');
    Route::post('/promote/{id}', [ExamResultController::class, 'promote'])->middleware('permission:exam.promote');

});
