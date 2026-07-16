<?php

use App\Modules\System\Package\Http\Controllers\PackageController;
use Illuminate\Support\Facades\Route;

Route::prefix('package')->middleware('auth.tera')->group(function () {

    Route::get('/list', [PackageController::class, 'list'])->middleware('permission:package.list');

});
