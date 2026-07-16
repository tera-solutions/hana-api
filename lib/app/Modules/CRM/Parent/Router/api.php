<?php

use App\Modules\CRM\Parent\Http\Controllers\ParentController;
use Illuminate\Support\Facades\Route;

Route::prefix('parent')->middleware('auth.tera')->group(function () {

    Route::get('/list', [ParentController::class, 'list'])->middleware('permission:parent.list');
    Route::get('/detail/{id}', [ParentController::class, 'detail'])->middleware('permission:parent.view');

    Route::post('/create', [ParentController::class, 'create'])
        ->middleware(['permission:parent.create', 'subscription.active', 'subscription.quota:parents']);
    Route::put('/update/{id}', [ParentController::class, 'update'])->middleware('permission:parent.update');

    Route::post('/suspend/{id}', [ParentController::class, 'suspend'])->middleware('permission:parent.suspend');
    Route::post('/restore/{id}', [ParentController::class, 'restore'])->middleware('permission:parent.restore');

});
