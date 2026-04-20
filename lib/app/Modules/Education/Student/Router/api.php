<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Education\Student\Http\Controllers\StudentController;

Route::prefix('student')->group(function () {

    Route::get('/list', [StudentController::class, 'list']);
    Route::get('/detail/{id}', [StudentController::class, 'detail']);

    Route::post('/create', [StudentController::class, 'create']);
    Route::put('/update/{id}', [StudentController::class, 'update']);
    Route::delete('/delete/{id}', [StudentController::class, 'delete']);
    Route::post('/export', [StudentController::class, 'export']);

});