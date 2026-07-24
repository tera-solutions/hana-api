<?php

use App\Modules\Education\CertificateTemplate\Http\Controllers\CertificateTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('certificate-template')->middleware('auth.tera')->group(function () {

    Route::get('/list', [CertificateTemplateController::class, 'list'])->middleware('permission:certificate_template.list');
    Route::get('/detail/{id}', [CertificateTemplateController::class, 'detail'])->middleware('permission:certificate_template.view');

    Route::post('/create', [CertificateTemplateController::class, 'create'])->middleware('permission:certificate_template.create');
    Route::put('/update/{id}', [CertificateTemplateController::class, 'update'])->middleware('permission:certificate_template.update');

    Route::post('/suspend/{id}', [CertificateTemplateController::class, 'suspend'])->middleware('permission:certificate_template.suspend');
    Route::post('/restore/{id}', [CertificateTemplateController::class, 'restore'])->middleware('permission:certificate_template.restore');

});
