<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseAuthController;
use App\Http\Controllers\Api\UnblockUserController;
use App\Http\Controllers\SwaggerTestController;
use App\Http\Controllers\Api\ProfileController;

Route::post('/firebase/register', [FirebaseAuthController::class, 'register']);
Route::post('/firebase/login', [FirebaseAuthController::class, 'login'])->middleware('login.attempt.limiter');
Route::post('/unblock', [UnblockUserController::class, 'unblock']);

Route::middleware('firebase.auth')->group(function () {
    Route::put('/firebase/profile', [FirebaseAuthController::class, 'update']);
});
Route::get('/swagger-test', [SwaggerTestController::class, 'test']);
