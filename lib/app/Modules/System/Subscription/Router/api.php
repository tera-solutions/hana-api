<?php

use App\Modules\System\Subscription\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('subscription')->middleware('auth.tera')->group(function () {

    Route::get('/current', [SubscriptionController::class, 'current'])->middleware('permission:subscription.view');
    Route::post('/upgrade', [SubscriptionController::class, 'upgrade'])->middleware('permission:subscription.update');
    Route::get('/invoice/list', [SubscriptionController::class, 'invoices'])->middleware('permission:subscription.view');

});
