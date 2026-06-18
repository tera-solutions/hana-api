<?php

use App\Modules\Education\StudentLevel\Http\Controllers\StudentLevelController;
use Illuminate\Support\Facades\Route;

Route::prefix('student-level')->middleware('auth.tera')->group(function () {

    Route::get('/detail/{studentId}', [StudentLevelController::class, 'detail'])->middleware('permission:student_level.view');
    Route::get('/history/{id}', [StudentLevelController::class, 'history'])->middleware('permission:student_level.history');

    Route::post('/placement', [StudentLevelController::class, 'placement'])->middleware('permission:student_level.placement');
    Route::post('/promote/{id}', [StudentLevelController::class, 'promote'])->middleware('permission:student_level.promote');
    Route::post('/adjust/{id}', [StudentLevelController::class, 'adjust'])->middleware('permission:student_level.adjust');

});
