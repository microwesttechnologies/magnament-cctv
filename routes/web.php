<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DvrController;
use App\Http\Controllers\InstallationOrderController;
use App\Http\Controllers\ProjectCameraController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TraceabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::get('/configuracion', [SettingsController::class, 'edit'])->name('configuracion');
    Route::put('/configuracion', [SettingsController::class, 'update'])->name('configuracion.update');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/floor-plans', [ProjectController::class, 'storeFloorPlan'])->name('projects.floor-plans.store');
    Route::delete('/projects/{project}/floor-plans/{floorPlan}', [ProjectController::class, 'destroyFloorPlan'])->name('projects.floor-plans.destroy');
    Route::post('/projects/{project}/cameras', [ProjectCameraController::class, 'store'])->name('projects.cameras.store');
    Route::put('/projects/{project}/cameras/{camera}', [ProjectCameraController::class, 'update'])->name('projects.cameras.update');
    Route::delete('/projects/{project}/cameras/{camera}', [ProjectCameraController::class, 'destroy'])->name('projects.cameras.destroy');

    Route::post('/projects/{project}/dvrs', [DvrController::class, 'store'])->name('projects.dvrs.store');
    Route::put('/projects/{project}/dvrs/{dvr}', [DvrController::class, 'update'])->name('projects.dvrs.update');
    Route::delete('/projects/{project}/dvrs/{dvr}', [DvrController::class, 'destroy'])->name('projects.dvrs.destroy');
    Route::get('/projects/{project}/dvrs/{dvr}', [DvrController::class, 'show'])->name('projects.dvrs.show');
    Route::post('/projects/{project}/dvrs/{dvr}/supports', [DvrController::class, 'storeSupport'])->name('projects.dvrs.supports.store');

    Route::get('/personal', [StaffController::class, 'index'])->name('staff.index');
    Route::get('/personal/crear', [StaffController::class, 'create'])->name('staff.create');
    Route::post('/personal', [StaffController::class, 'store'])->name('staff.store');
    Route::get('/personal/{staff}/editar', [StaffController::class, 'edit'])->name('staff.edit');
    Route::put('/personal/{staff}', [StaffController::class, 'update'])->name('staff.update');
    Route::delete('/personal/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');

    Route::get('/cotizaciones', [QuotationController::class, 'index'])->name('cotizaciones');
    Route::get('/cotizaciones/crear', [QuotationController::class, 'createStandalone'])->name('quotations.create');
    Route::post('/cotizaciones', [QuotationController::class, 'storeStandalone'])->name('quotations.store');
    Route::post('/cotizaciones/proyectos', [QuotationController::class, 'storeQuickProject'])->name('quotations.projects.store');
    Route::get('/projects/{project}/cotizaciones/crear', [QuotationController::class, 'create'])->name('projects.quotations.create');
    Route::post('/projects/{project}/cotizaciones', [QuotationController::class, 'store'])->name('projects.quotations.store');
    Route::get('/projects/{project}/cotizaciones/{quotation}', [QuotationController::class, 'show'])->name('projects.quotations.show');
    Route::get('/projects/{project}/cotizaciones/{quotation}/editar', [QuotationController::class, 'edit'])->name('projects.quotations.edit');
    Route::put('/projects/{project}/cotizaciones/{quotation}', [QuotationController::class, 'update'])->name('projects.quotations.update');
    Route::post('/projects/{project}/cotizaciones/{quotation}/estado', [QuotationController::class, 'transition'])->name('projects.quotations.transition');
    Route::get('/projects/{project}/cotizaciones/{quotation}/pdf', [QuotationController::class, 'downloadPdf'])->name('projects.quotations.pdf');
    Route::post('/projects/{project}/cotizaciones/{quotation}/convertir', [QuotationController::class, 'convert'])->name('projects.quotations.convert');

    Route::get('/projects/{project}/ordenes/{order}', [InstallationOrderController::class, 'show'])->name('projects.orders.show');
    Route::get('/trazabilidad', [TraceabilityController::class, 'index'])->name('trazabilidad');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
