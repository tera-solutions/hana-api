<?php

use App\Modules\Education\CourseCurriculum\Http\Controllers\CourseCurriculumController;
use Illuminate\Support\Facades\Route;

Route::prefix('course-curriculum')->middleware('auth.tera')->group(function () {

    Route::get('/list', [CourseCurriculumController::class, 'list'])->middleware('permission:course_curriculum.list');
    Route::get('/detail/{id}', [CourseCurriculumController::class, 'detail'])->middleware('permission:course_curriculum.view');
    Route::post('/create', [CourseCurriculumController::class, 'create'])->middleware('permission:course_curriculum.create');
    Route::put('/update/{id}', [CourseCurriculumController::class, 'update'])->middleware('permission:course_curriculum.update');
    Route::delete('/delete/{id}', [CourseCurriculumController::class, 'delete'])->middleware('permission:course_curriculum.delete');

});
