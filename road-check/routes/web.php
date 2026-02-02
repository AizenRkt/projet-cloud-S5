<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseAuthController;
use App\Http\Controllers\Web\FirebaseWebController;
use App\Http\Controllers\SignalementController;


Route::get('/signalements', [SignalementController::class, 'index']);
Route::post('/signalements', [SignalementController::class, 'store']);
Route::put('/signalements/{id}', [SignalementController::class, 'update']);
Route::get('/entreprises', [SignalementController::class, 'getEntreprises']);
Route::get('/utilisateurs', [SignalementController::class, 'getUtilisateurs']);
Route::get('/map', function () {
    return view('map');
});


Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [FirebaseWebController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [FirebaseWebController::class, 'register'])->name('register.submit');

Route::get('/login', [FirebaseWebController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [FirebaseWebController::class, 'login'])->name('login.submit');

Route::middleware('firebase.auth')->group(function () {
    Route::get('/profile', [FirebaseWebController::class, 'profile'])->name('profile');
    Route::get('/profile/edit', [FirebaseWebController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [FirebaseWebController::class, 'update'])->name('profile.update');
});

// Pages classiques
Route::get('/', function () { return view('welcome'); });
Route::get('/about', function () { return view('about'); });
Route::get('/contact', function () { return view('contact', ['email'=>'contact@example.com']); });
Route::get('/user/{name?}', function ($name='Invité') { return "Bonjour, $name!"; });
Route::get('/hello', function () { return 'Bonjour Laravel!'; });
