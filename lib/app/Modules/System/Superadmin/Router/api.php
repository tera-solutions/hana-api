<?php

use App\Modules\System\Superadmin\Http\Controllers\DashboardController;
use App\Modules\System\Superadmin\Http\Controllers\PackageController;
use App\Modules\System\Superadmin\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('superadmin')->middleware(['auth.tera', 'superadmin'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::prefix('tenant')->group(function () {
        Route::get('/list', [TenantController::class, 'list']);
        Route::get('/detail/{id}', [TenantController::class, 'detail']);
        Route::post('/suspend/{id}', [TenantController::class, 'suspend']);
        Route::post('/activate/{id}', [TenantController::class, 'activate']);

        Route::post('/{id}/subscription/assign', [TenantController::class, 'assignPlan']);
        Route::post('/{id}/subscription/extend', [TenantController::class, 'extendSubscription']);
        Route::post('/{id}/subscription/cancel', [TenantController::class, 'cancelSubscription']);
    });

    Route::prefix('package')->group(function () {
        Route::get('/list', [PackageController::class, 'list']);
        Route::get('/detail/{id}', [PackageController::class, 'detail']);
        Route::post('/create', [PackageController::class, 'create']);
        Route::put('/update/{id}', [PackageController::class, 'update']);
        Route::post('/activate/{id}', [PackageController::class, 'activate']);
        Route::post('/deactivate/{id}', [PackageController::class, 'deactivate']);
    });

});
