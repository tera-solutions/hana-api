<?php

use App\Modules\HR\Teacher\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')->middleware('auth.tera')->group(function () {

    Route::get('/list', [TeacherController::class, 'list'])->middleware('permission:teacher.list');
    Route::get('/detail/{id}', [TeacherController::class, 'detail'])->middleware('permission:teacher.view');

    Route::post('/create', [TeacherController::class, 'create'])->middleware('permission:teacher.create');
    Route::put('/update/{id}', [TeacherController::class, 'update'])->middleware('permission:teacher.update');
    Route::delete('/delete/{id}', [TeacherController::class, 'delete'])->middleware('permission:teacher.delete');

});
