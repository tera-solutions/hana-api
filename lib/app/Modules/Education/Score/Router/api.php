<?php

use App\Modules\Education\Score\Http\Controllers\ScoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('score')->middleware('auth.tera')->group(function () {
    Route::get('/{classId}/config', [ScoreController::class, 'getConfig'])->middleware('permission:score.view')->whereNumber('classId');
    Route::put('/{classId}/config', [ScoreController::class, 'saveConfig'])->middleware('permission:score.configure')->whereNumber('classId');

    Route::get('/{classId}/board', [ScoreController::class, 'board'])->middleware('permission:score.view')->whereNumber('classId');
    Route::post('/{classId}/component', [ScoreController::class, 'saveComponent'])->middleware('permission:score.update')->whereNumber('classId');

    Route::post('/{classId}/finalize', [ScoreController::class, 'finalize'])->middleware('permission:score.finalize')->whereNumber('classId');
    Route::post('/{classId}/unlock', [ScoreController::class, 'unlock'])->middleware('permission:score.finalize')->whereNumber('classId');
});
