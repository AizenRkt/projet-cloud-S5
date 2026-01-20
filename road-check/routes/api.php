<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseAuthController;

Route::post('/firebase/register', [FirebaseAuthController::class, 'register']);
Route::post('/firebase/login', [FirebaseAuthController::class, 'login']);

Route::middleware('firebase.auth')->group(function () {
    Route::put('/firebase/profile', [FirebaseAuthController::class, 'update']);
});
