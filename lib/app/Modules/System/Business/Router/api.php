<?php

use App\Modules\System\Business\Http\Controllers\BusinessController;
use Illuminate\Support\Facades\Route;

Route::prefix('business')->middleware('auth.tera')->group(function () {

    Route::get('/list', [BusinessController::class, 'list'])->middleware('permission:business.list');
    Route::get('/detail/{id}', [BusinessController::class, 'detail'])->middleware('permission:business.view');

    Route::post('/create', [BusinessController::class, 'create'])->middleware('permission:business.create');
    Route::put('/update/{id}', [BusinessController::class, 'update'])->middleware('permission:business.update');
    Route::delete('/delete/{id}', [BusinessController::class, 'delete'])->middleware('permission:business.delete');

});
