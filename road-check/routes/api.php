<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UnblockUserController;
use App\Http\Controllers\SwaggerTestController;
use App\Http\Controllers\Api\ProfileController;

Route::post('/unblock', [UnblockUserController::class, 'unblock']);
Route::get('/swagger-test', [SwaggerTestController::class, 'test']);
