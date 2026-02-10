<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UnblockUserController;
use App\Http\Controllers\SwaggerTestController;
use App\Http\Controllers\Api\ProfileController;

use App\Http\Controllers\Api\FirebaseAuthController;

Route::post('/login', [FirebaseAuthController::class, 'login']);
Route::post('/register', [FirebaseAuthController::class, 'register']);
Route::post('/unblock', [UnblockUserController::class, 'unblock']);
Route::get('/swagger-test', [SwaggerTestController::class, 'test']);
