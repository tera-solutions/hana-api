<?php

use App\Modules\HR\Teacher\Http\Controllers\TeacherCertificateController;
use App\Modules\HR\Teacher\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')->middleware('auth.tera')->group(function () {

    Route::get('/list', [TeacherController::class, 'list'])->middleware('permission:teacher.list');
    Route::get('/detail/{id}', [TeacherController::class, 'detail'])->middleware('permission:teacher.view');

    Route::post('/create', [TeacherController::class, 'create'])->middleware('permission:teacher.create');
    Route::put('/update/{id}', [TeacherController::class, 'update'])->middleware('permission:teacher.update');

    Route::post('/suspend/{id}', [TeacherController::class, 'suspend'])->middleware('permission:teacher.suspend');
    Route::post('/restore/{id}', [TeacherController::class, 'restore'])->middleware('permission:teacher.restore');
    Route::post('/resign/{id}', [TeacherController::class, 'resign'])->middleware('permission:teacher.resign');

    // Certificates (teacher.md §7 / §11).
    Route::get('/certificate/list/{teacherId}', [TeacherCertificateController::class, 'list'])->middleware('permission:teacher.view');
    Route::post('/certificate/create/{teacherId}', [TeacherCertificateController::class, 'create'])->middleware('permission:teacher.update');
    Route::put('/certificate/update/{id}', [TeacherCertificateController::class, 'update'])->middleware('permission:teacher.update');
    Route::delete('/certificate/delete/{id}', [TeacherCertificateController::class, 'delete'])->middleware('permission:teacher.update');

});
