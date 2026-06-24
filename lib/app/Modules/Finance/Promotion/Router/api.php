<?php

use App\Modules\Finance\Promotion\Http\Controllers\PromotionController;
use App\Modules\Finance\Promotion\Http\Controllers\ReferralController;
use Illuminate\Support\Facades\Route;

// Promotion programmes, vouchers, apply engine + referrals (promotion.md §XIV).
Route::prefix('promotion')->middleware('auth.tera')->group(function () {

    Route::get('/list', [PromotionController::class, 'list'])->middleware('permission:promotion.list');
    Route::get('/detail/{id}', [PromotionController::class, 'detail'])->middleware('permission:promotion.view');

    Route::post('/create', [PromotionController::class, 'create'])->middleware('permission:promotion.create');
    Route::put('/update/{id}', [PromotionController::class, 'update'])->middleware('permission:promotion.update');

    Route::post('/activate/{id}', [PromotionController::class, 'activate'])->middleware('permission:promotion.approve');
    Route::post('/pause/{id}', [PromotionController::class, 'pause'])->middleware('permission:promotion.stop');
    Route::post('/close/{id}', [PromotionController::class, 'close'])->middleware('permission:promotion.stop');

    // Vouchers reuse the promotion's codes (writes → update, apply → apply).
    Route::post('/generate-vouchers/{id}', [PromotionController::class, 'generateVouchers'])->middleware('permission:promotion.update');
    Route::post('/import-vouchers/{id}', [PromotionController::class, 'importVouchers'])->middleware('permission:promotion.update');
    Route::post('/voucher/validate', [PromotionController::class, 'validateVoucher'])->middleware('permission:promotion.apply');

    Route::post('/apply', [PromotionController::class, 'apply'])->middleware('permission:promotion.apply');

    // Referral programme.
    Route::get('/referral/list', [ReferralController::class, 'list'])->middleware('permission:promotion.view');
    Route::post('/referral/create', [ReferralController::class, 'create'])->middleware('permission:promotion.create');
    Route::post('/referral/reward/{id}', [ReferralController::class, 'reward'])->middleware('permission:promotion.apply');

});
