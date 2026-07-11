<?php

use App\Modules\Education\ClassSessionFeedback\Http\Controllers\ClassSessionFeedbackController;
use Illuminate\Support\Facades\Route;

// Per-student notes for class sessions (class-session.md §13, §15).
Route::prefix('session-feedback')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ClassSessionFeedbackController::class, 'list'])->middleware('permission:session_feedback.list');
    Route::post('/create', [ClassSessionFeedbackController::class, 'create'])->middleware('permission:session_feedback.create');

});
