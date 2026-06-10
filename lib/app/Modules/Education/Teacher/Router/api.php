<?php

use App\Modules\Education\Teacher\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')->group(function () {

    Route::get('/list', [TeacherController::class, 'list']);
    Route::get('/detail/{id}', [TeacherController::class, 'detail']);

    Route::post('/create', [TeacherController::class, 'create']);
    Route::put('/update/{id}', [TeacherController::class, 'update']);
    Route::delete('/delete/{id}', [TeacherController::class, 'delete']);

});
