<?php

use App\Modules\Education\Dashboard\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->middleware('auth.tera')->group(function () {

    Route::get('/summary', [DashboardController::class, 'summary']);

});
