<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Education\Course\Http\Controllers\CourseController;

Route::prefix('course')->group(function () {
    Route::get('/list', [CourseController::class, 'list']);
    Route::get('/detail/{id}', [CourseController::class, 'detail']);
    Route::post('/create', [CourseController::class, 'create']);
    Route::put('/update/{id}', [CourseController::class, 'update']);
    Route::delete('/delete/{id}', [CourseController::class, 'delete']);

});