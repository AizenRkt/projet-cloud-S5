<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseAuthController;

// 🔹 Routes Firebase (API test dans web.php)
Route::post('/firebase/register', [FirebaseAuthController::class, 'register']);
Route::post('/firebase/login', [FirebaseAuthController::class, 'login']);

Route::middleware('firebase.auth')->group(function () {
    Route::put('/firebase/profile', [FirebaseAuthController::class, 'update']);
});

// 🔹 Routes Web / Views
Route::get('/', function () {
    return view('welcome');
});

Route::get('/hello', function () {
    return 'Bonjour Laravel!';
});

// 🔹 Route user optionnelle (éviter conflit)
Route::get('/user/{name?}', function ($name = 'Invité') {
    return "Bonjour, $name!";
});

// 🔹 Pages
Route::get('/about', function () {
    return view('about');
});

Route::get('/contact', function () {
    $email = 'contact@example.com';
    return view('contact', ['email' => $email]);
});
