<?php

use App\Modules\Finance\SubscriptionPackage\Http\Controllers\SubscriptionPackageController;
use Illuminate\Support\Facades\Route;

Route::prefix('subscription-package')->middleware('auth.tera')->group(function () {

    Route::get('/list', [SubscriptionPackageController::class, 'list'])->middleware('permission:subscription_package.list');
    Route::get('/detail/{id}', [SubscriptionPackageController::class, 'detail'])->middleware('permission:subscription_package.view');
    Route::get('/usages/{id}', [SubscriptionPackageController::class, 'usages'])->middleware('permission:subscription_package.view');

    Route::post('/create', [SubscriptionPackageController::class, 'create'])->middleware('permission:subscription_package.create');
    Route::put('/update/{id}', [SubscriptionPackageController::class, 'update'])->middleware('permission:subscription_package.update');
    Route::put('/discount-rules/{id}', [SubscriptionPackageController::class, 'setDiscountRules'])->middleware('permission:subscription_package.update');

    Route::post('/toggle/{id}', [SubscriptionPackageController::class, 'toggle'])->middleware('permission:subscription_package.toggle');
    Route::post('/delete/{id}', [SubscriptionPackageController::class, 'delete'])->middleware('permission:subscription_package.delete');

});
