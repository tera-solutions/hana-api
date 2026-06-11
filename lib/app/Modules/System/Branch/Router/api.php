<?php

use App\Modules\System\Branch\Http\Controllers\BranchController;
use Illuminate\Support\Facades\Route;

Route::prefix('branch')->middleware('auth.tera')->group(function () {

    Route::get('/list', [BranchController::class, 'list'])->middleware('permission:branch.list');
    Route::get('/detail/{id}', [BranchController::class, 'detail'])->middleware('permission:branch.view');

    Route::post('/create', [BranchController::class, 'create'])->middleware('permission:branch.create');
    Route::put('/update/{id}', [BranchController::class, 'update'])->middleware('permission:branch.update');
    Route::delete('/delete/{id}', [BranchController::class, 'delete'])->middleware('permission:branch.delete');

});
