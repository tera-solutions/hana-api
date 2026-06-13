<?php

use App\Modules\CRM\Lead\Http\Controllers\LeadController;
use App\Modules\CRM\Lead\Http\Controllers\LeadGuardianController;
use App\Modules\CRM\Lead\Http\Controllers\LeadStudentController;
use Illuminate\Support\Facades\Route;

// Lead CRUD + lifecycle (lead.md §2–§7 / "CRM Lead" API).
Route::prefix('leads')->middleware('auth.tera')->group(function () {

    Route::get('/', [LeadController::class, 'list'])->middleware('permission:crm_lead.list');
    Route::post('/', [LeadController::class, 'create'])->middleware('permission:crm_lead.create');
    Route::get('/{id}', [LeadController::class, 'detail'])->middleware('permission:crm_lead.view');
    Route::put('/{id}', [LeadController::class, 'update'])->middleware('permission:crm_lead.update');

    Route::post('/{id}/suspend', [LeadController::class, 'suspend'])->middleware('permission:crm_lead.suspend');
    Route::post('/{id}/restore', [LeadController::class, 'restore'])->middleware('permission:crm_lead.restore');
});

// Guardians nested under a lead (lead.md §8 "Quản lý Người giám hộ").
Route::prefix('leads/{leadId}/guardians')->middleware('auth.tera')->group(function () {

    Route::get('/', [LeadGuardianController::class, 'list'])->middleware('permission:crm_lead.view');

    Route::post('/add', [LeadGuardianController::class, 'create'])->middleware('permission:crm_lead.update');
    Route::put('/update/{id}', [LeadGuardianController::class, 'update'])->middleware('permission:crm_lead.update');
    Route::delete('/delete/{id}', [LeadGuardianController::class, 'delete'])->middleware('permission:crm_lead.update');

});

// Student links nested under a lead (lead.md §9 / "Student Relation" API).
Route::prefix('leads/{leadId}/students')->middleware('auth.tera')->group(function () {

    Route::get('/', [LeadStudentController::class, 'list'])->middleware('permission:crm_lead.view');

    Route::post('/add', [LeadStudentController::class, 'create'])->middleware('permission:crm_lead.update');
    Route::put('/update/{id}', [LeadStudentController::class, 'update'])->middleware('permission:crm_lead.update');
    Route::delete('/delete/{id}', [LeadStudentController::class, 'delete'])->middleware('permission:crm_lead.update');

});
