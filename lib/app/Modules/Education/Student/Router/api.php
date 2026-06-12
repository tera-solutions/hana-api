<?php

use App\Modules\Education\Student\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('student')->middleware('auth.tera')->group(function () {

    Route::get('/list', [StudentController::class, 'list'])->middleware('permission:student.list');
    Route::get('/detail/{id}', [StudentController::class, 'detail'])->middleware('permission:student.view');

    Route::post('/create', [StudentController::class, 'create'])->middleware('permission:student.create');
    Route::put('/update/{id}', [StudentController::class, 'update'])->middleware('permission:student.update');

    Route::post('/suspend/{id}', [StudentController::class, 'suspend'])->middleware('permission:student.suspend');
    Route::post('/restore/{id}', [StudentController::class, 'restore'])->middleware('permission:student.restore');

    Route::delete('/delete/{id}', [StudentController::class, 'delete'])->middleware('permission:student.delete');
    Route::post('/export', [StudentController::class, 'export'])->middleware('permission:student.list');

});
