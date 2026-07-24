<?php

use App\Modules\Education\Certificate\Http\Controllers\CertificateController;
use Illuminate\Support\Facades\Route;

// Public QR verification — no auth, no tenant scope (see CertificateService::verify()).
Route::get('/certificate/verify/{token}', [CertificateController::class, 'verify']);

Route::prefix('certificate')->middleware('auth.tera')->group(function () {
    Route::get('/list', [CertificateController::class, 'list'])
        ->middleware('permission:certificate.list');
    Route::get('/eligible-students', [CertificateController::class, 'eligibleStudents'])
        ->middleware('permission:certificate.view');
    Route::get('/download/{id}', [CertificateController::class, 'download'])
        ->middleware('permission:certificate.view')->whereNumber('id');
    Route::post('/issue-bulk', [CertificateController::class, 'issueBulk'])
        ->middleware('permission:certificate.issue');

    Route::get('/{classId}/eligibility', [CertificateController::class, 'eligibility'])
        ->middleware('permission:certificate.view')->whereNumber('classId');
    Route::get('/{classId}/list', [CertificateController::class, 'listByClass'])
        ->middleware('permission:certificate.view')->whereNumber('classId');
    Route::get('/student/{studentId}/list', [CertificateController::class, 'listByStudent'])
        ->middleware('permission:certificate.view')->whereNumber('studentId');
    Route::post('/{classId}/issue', [CertificateController::class, 'issue'])
        ->middleware('permission:certificate.issue')->whereNumber('classId');
    Route::post('/revoke/{id}', [CertificateController::class, 'revoke'])
        ->middleware('permission:certificate.revoke')->whereNumber('id');
});
