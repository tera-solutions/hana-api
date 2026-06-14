<?php

use App\Modules\Education\ClassSession\Http\Controllers\ClassSessionController;
use Illuminate\Support\Facades\Route;

// Session (buổi học) management (spec §12).

// Class-scoped operations — nested under a class.
Route::prefix('class-room')->middleware('auth.tera')->group(function () {

    Route::get('/{classId}/session/list', [ClassSessionController::class, 'list'])->middleware('permission:session.list');
    Route::post('/{classId}/session/create', [ClassSessionController::class, 'create'])->middleware('permission:session.create');
    Route::post('/{classId}/session/generate', [ClassSessionController::class, 'generate'])->middleware('permission:session.generate');

});

// Individual session management — by session id.
Route::prefix('class-session')->middleware('auth.tera')->group(function () {

    Route::get('/detail/{id}', [ClassSessionController::class, 'detail'])->middleware('permission:session.view');
    Route::put('/update/{id}', [ClassSessionController::class, 'update'])->middleware('permission:session.update');
    Route::post('/cancel/{id}', [ClassSessionController::class, 'cancel'])->middleware('permission:session.cancel');
    Route::delete('/delete/{id}', [ClassSessionController::class, 'delete'])->middleware('permission:session.delete');

});
