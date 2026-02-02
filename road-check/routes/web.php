<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseAuthController;


use App\Http\Controllers\Web\FirebaseWebController;

Route::get('/register', [FirebaseWebController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [FirebaseWebController::class, 'register'])->name('register.submit');

Route::get('/login', [FirebaseWebController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [FirebaseWebController::class, 'login'])->name('login.submit');

Route::middleware('firebase.auth')->group(function () {
    Route::get('/profile', [FirebaseWebController::class, 'profile'])->name('profile');
    Route::get('/profile/edit', [FirebaseWebController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [FirebaseWebController::class, 'update'])->name('profile.update');
    Route::get('/unblock', function() { return view('firebase.unblock'); })->name('unblock.form');
    Route::post('/unblock', [\App\Http\Controllers\Api\UnblockUserController::class, 'unblock'])->name('unblock.submit');

    // Page React de gestion des profils (affichage)
    Route::get('/profiles', [FirebaseWebController::class, 'profile'])->name('profiles.manage');
});

// Pages classiques
Route::get('/', function () { return view('welcome'); });
Route::get('/about', function () { return view('about'); });
Route::get('/contact', function () { return view('contact', ['email'=>'contact@example.com']); });
Route::get('/user/{name?}', function ($name='Invité') { return "Bonjour, $name!"; });
Route::get('/hello', function () { return 'Bonjour Laravel!'; });
