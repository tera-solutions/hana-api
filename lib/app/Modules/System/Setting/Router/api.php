<?php

use App\Modules\System\Setting\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('setting')->middleware('auth.tera')->group(function () {

    Route::get('/list', [SettingController::class, 'list'])->middleware('permission:setting.list');
    Route::get('/detail/{id}', [SettingController::class, 'detail'])->middleware('permission:setting.view');

    Route::post('/create', [SettingController::class, 'create'])->middleware('permission:setting.update');
    Route::put('/update/{id}', [SettingController::class, 'update'])->middleware('permission:setting.update');
    Route::post('/upsert', [SettingController::class, 'upsert'])->middleware('permission:setting.update');
    Route::delete('/delete/{id}', [SettingController::class, 'delete'])->middleware('permission:setting.update');

});
