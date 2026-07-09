<?php

use App\Modules\HR\Achievement\Http\Controllers\AchievementController;
use App\Modules\HR\Achievement\Http\Controllers\TeacherReviewController;
use Illuminate\Support\Facades\Route;

Route::prefix('achievement')->middleware('auth.tera')->group(function () {

    Route::get('/summary', [AchievementController::class, 'summary'])->middleware('permission:achievement.view');
    Route::get('/progress', [AchievementController::class, 'progress'])->middleware('permission:achievement.view');

});

Route::prefix('teacher-review')->middleware('auth.tera')->group(function () {

    Route::get('/list', [TeacherReviewController::class, 'list'])->middleware('permission:achievement.view');
    Route::post('/create', [TeacherReviewController::class, 'create'])->middleware('permission:teacher_review.create');

});
