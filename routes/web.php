<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DvrController;
use App\Http\Controllers\ProjectCameraController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
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

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
