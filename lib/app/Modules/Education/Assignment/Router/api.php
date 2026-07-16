<?php

use App\Modules\Education\Assignment\Http\Controllers\AssignmentController;
use App\Modules\Education\Assignment\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('assignment')->middleware(['auth.tera', 'subscription.feature:assignments'])->group(function () {

    Route::get('/list', [AssignmentController::class, 'list'])->middleware('permission:assignment.list');
    Route::get('/summary', [AssignmentController::class, 'summary'])->middleware('permission:assignment.list');
    Route::get('/detail/{id}', [AssignmentController::class, 'detail'])->middleware('permission:assignment.view');

    Route::post('/create', [AssignmentController::class, 'create'])->middleware('permission:assignment.create');
    Route::put('/update/{id}', [AssignmentController::class, 'update'])->middleware('permission:assignment.update');

    Route::post('/publish/{id}', [AssignmentController::class, 'publish'])->middleware('permission:assignment.update');
    Route::delete('/delete/{id}', [AssignmentController::class, 'delete'])->middleware('permission:assignment.delete');

    // Giao bài theo lớp / nhóm trình độ / học viên / bài học (assignment.md §7).
    Route::post('/assign/class/{id}', [AssignmentController::class, 'assignByClass'])->middleware('permission:assignment.assign');
    Route::post('/assign/group/{id}', [AssignmentController::class, 'assignByGroup'])->middleware('permission:assignment.assign');
    Route::post('/assign/student/{id}', [AssignmentController::class, 'assignByStudent'])->middleware('permission:assignment.assign');
    Route::post('/assign/lesson/{id}', [AssignmentController::class, 'assignByLesson'])->middleware('permission:assignment.assign');

    // Nộp bài (assignment.md §8).
    Route::post('/submit/{id}', [AssignmentController::class, 'submit'])->middleware('permission:assignment.update');

});

// Submissions — grading queue, results, grade & publish (assignment.md §9, §10, §XII).
Route::prefix('submission')->middleware(['auth.tera', 'subscription.feature:assignments'])->group(function () {

    // Danh sách học viên nộp bài (chờ chấm) / Danh sách bài tập đã chấm / Chi tiết bài đã chấm.
    Route::get('/submitted/{assignmentId}', [SubmissionController::class, 'submitted'])->middleware('permission:assignment.grade');
    Route::get('/graded/{assignmentId}', [SubmissionController::class, 'graded'])->middleware('permission:assignment.result');
    Route::get('/detail/{id}', [SubmissionController::class, 'detail'])->middleware('permission:assignment.result');

    Route::post('/grade/{id}', [SubmissionController::class, 'grade'])->middleware('permission:assignment.grade');
    Route::put('/update/{id}', [SubmissionController::class, 'update'])->middleware('permission:assignment.grade');
    Route::post('/publish/{id}', [SubmissionController::class, 'publish'])->middleware('permission:assignment.result');

});
