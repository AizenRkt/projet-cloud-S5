<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EntrepriseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SignalementController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\UserController;

Route::middleware('role:visitor,user,manager')->group(function () {
    Route::get('signalements', [SignalementController::class, 'index']);
    Route::get('stats/global', [StatsController::class, 'global']);
    Route::get('entreprises', [EntrepriseController::class, 'index']);
});

Route::middleware('role:user,manager')->group(function () {
    Route::post('signalements', [SignalementController::class, 'store']);
});

Route::middleware('role:manager')->group(function () {
    Route::put('signalements/{signalement}', [SignalementController::class, 'update']);
    Route::post('signalements/sync', [SyncController::class, 'sync']);
    Route::get('roles', [RoleController::class, 'index']);
    Route::apiResource('users', UserController::class);
});
