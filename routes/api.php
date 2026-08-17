<?php

use App\Http\Controllers\CameraController;
use Illuminate\Support\Facades\Route;

Route::prefix('cameras')->group(function () {
    Route::get('/', [CameraController::class, 'index']);
    Route::post('/', [CameraController::class, 'store']);
    Route::get('/{id}', [CameraController::class, 'show']);
});
