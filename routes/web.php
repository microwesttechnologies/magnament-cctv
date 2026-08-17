<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DvrController;
use App\Http\Controllers\InstallationOrderController;
use App\Http\Controllers\ProjectCameraController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TraceabilityController;
use App\Http\Controllers\Technician\TechnicianAuthController;
use App\Http\Controllers\Technician\TechnicianOrderController;
use App\Http\Controllers\Technician\TechnicianPwaController;
use App\Http\Middleware\PreventPublicHttpCache;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login', request()->only('source'));
});

Route::get('/manifest.webmanifest', [TechnicianPwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/manifest-tecnico.webmanifest', [TechnicianPwaController::class, 'manifest'])->name('technician.manifest');
Route::get('/sw.js', [TechnicianPwaController::class, 'serviceWorker'])->name('pwa.sw');
Route::get('/tecnico/sw.js', [TechnicianPwaController::class, 'serviceWorker'])->name('technician.sw');
Route::get('/tecnico/offline.html', [TechnicianPwaController::class, 'offline'])->name('technician.offline');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/tecnico/login', [TechnicianAuthController::class, 'show'])->name('technician.login');
    Route::post('/tecnico/login', [TechnicianAuthController::class, 'store'])->name('technician.login.store');
});

Route::middleware(['auth', PreventPublicHttpCache::class, 'office'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/configuracion', [SettingsController::class, 'edit'])->name('configuracion');
    Route::put('/configuracion', [SettingsController::class, 'update'])->name('configuracion.update');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/{project}/floor-plans', [ProjectController::class, 'storeFloorPlan'])->name('projects.floor-plans.store');
    Route::put('/projects/{project}/floor-plans/{floorPlan}', [ProjectController::class, 'updateFloorPlan'])->name('projects.floor-plans.update');
    Route::post('/projects/{project}/floor-plans/reorder', [ProjectController::class, 'reorderFloorPlans'])->name('projects.floor-plans.reorder');
    Route::delete('/projects/{project}/floor-plans/{floorPlan}', [ProjectController::class, 'destroyFloorPlan'])->name('projects.floor-plans.destroy');
    Route::post('/projects/{project}/cameras', [ProjectCameraController::class, 'store'])->name('projects.cameras.store');
    Route::put('/projects/{project}/cameras/{camera}', [ProjectCameraController::class, 'update'])->name('projects.cameras.update');
    Route::patch('/projects/{project}/cameras/{camera}/position', [ProjectCameraController::class, 'updatePosition'])->name('projects.cameras.position');
    Route::post('/projects/{project}/cameras/{camera}/unplace', [ProjectCameraController::class, 'unplace'])->name('projects.cameras.unplace');
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

    Route::get('/ordenes', [ServiceOrderController::class, 'index'])->name('service-orders.index');
    Route::get('/ordenes/crear', [ServiceOrderController::class, 'create'])->name('service-orders.create');
    Route::post('/ordenes', [ServiceOrderController::class, 'store'])->name('service-orders.store');
    Route::get('/ordenes/{order}', [ServiceOrderController::class, 'show'])->name('service-orders.show');
    Route::post('/ordenes/{order}/asignar', [ServiceOrderController::class, 'assign'])->name('service-orders.assign');
    Route::post('/ordenes/{order}/reasignar', [ServiceOrderController::class, 'reassign'])->name('service-orders.reassign');
    Route::post('/ordenes/{order}/prioridad', [ServiceOrderController::class, 'updatePriority'])->name('service-orders.priority');
    Route::post('/ordenes/{order}/cancelar', [ServiceOrderController::class, 'cancel'])->name('service-orders.cancel');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

Route::middleware([PreventPublicHttpCache::class, 'technician'])->prefix('tecnico')->group(function () {
    Route::post('/logout', [TechnicianAuthController::class, 'destroy'])->name('technician.logout');

    Route::middleware('technician.mobile')->group(function () {
        Route::get('/', [TechnicianOrderController::class, 'home'])->name('technician.home');
        Route::get('/ordenes', [TechnicianOrderController::class, 'index'])->name('technician.orders.index');
        Route::get('/ordenes/{order}', [TechnicianOrderController::class, 'show'])->name('technician.orders.show');
        Route::post('/ordenes/{order}/iniciar', [TechnicianOrderController::class, 'start'])->name('technician.orders.start');
        Route::post('/ordenes/{order}/evidencia', [TechnicianOrderController::class, 'evidence'])->name('technician.orders.evidence');
        Route::post('/ordenes/{order}/resolver', [TechnicianOrderController::class, 'resolve'])->name('technician.orders.resolve');
        Route::post('/ordenes/{order}/cancelar', [TechnicianOrderController::class, 'cancel'])->name('technician.orders.cancel');
        Route::get('/perfil', [TechnicianOrderController::class, 'profile'])->name('technician.profile');
        Route::get('/notificaciones', [TechnicianOrderController::class, 'notifications'])->name('technician.notifications');
        Route::post('/push/subscribe', [TechnicianOrderController::class, 'subscribePush'])->name('technician.push.subscribe');
        Route::get('/push/vapid', [TechnicianOrderController::class, 'vapidKey'])->name('technician.push.vapid');
    });
});
