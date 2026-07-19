<?php

use App\Modules\Education\PlacementTest\Http\Controllers\PlacementTestController;
use Illuminate\Support\Facades\Route;

Route::prefix('placement-test')->middleware('auth.tera')->group(function () {

    Route::get('/list', [PlacementTestController::class, 'list'])->middleware('permission:placement_test.list');
    Route::get('/detail/{id}', [PlacementTestController::class, 'detail'])->middleware('permission:placement_test.view');

    Route::post('/create', [PlacementTestController::class, 'create'])->middleware('permission:placement_test.create');
    Route::put('/update/{id}', [PlacementTestController::class, 'update'])->middleware('permission:placement_test.update');
    Route::post('/publish/{id}', [PlacementTestController::class, 'publish'])->middleware('permission:placement_test.update');
    Route::delete('/delete/{id}', [PlacementTestController::class, 'delete'])->middleware('permission:placement_test.delete');

    Route::get('/results/{id}', [PlacementTestController::class, 'results'])->middleware('permission:placement_test.view');
    Route::post('/results/{id}', [PlacementTestController::class, 'recordResult'])->middleware('permission:placement_test.update');

    Route::post('/generate-questions/{id}', [PlacementTestController::class, 'generateQuestions'])->middleware('permission:placement_test.update');

});
