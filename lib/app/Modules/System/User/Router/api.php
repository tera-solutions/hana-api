<?php

use App\Modules\System\User\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware('auth.tera')->group(function () {

    Route::get('/list', [UserController::class, 'list'])->middleware('permission:user.list');
    Route::get('/detail/{id}', [UserController::class, 'detail'])->middleware('permission:user.view');

    Route::post('/create', [UserController::class, 'create'])->middleware('permission:user.create');
    Route::put('/update/{id}', [UserController::class, 'update'])->middleware('permission:user.update');
    Route::delete('/delete/{id}', [UserController::class, 'delete'])->middleware('permission:user.delete');

    Route::post('/activate/{id}', [UserController::class, 'activate'])->middleware('permission:user.activate');
    Route::post('/deactivate/{id}', [UserController::class, 'deactivate'])->middleware('permission:user.deactivate');
    Route::post('/unlock/{id}', [UserController::class, 'unlock'])->middleware('permission:user.unlock');
    Route::post('/reset-password/{id}', [UserController::class, 'resetPassword'])->middleware('permission:user.reset_password');

});
