<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseAuthController;
use App\Http\Controllers\Web\FirebaseWebController;
use App\Http\Controllers\SignalementController;

// ==================== API Routes (pour le frontend React) ====================

// Signalements
Route::get('/api/signalements', [SignalementController::class, 'index']);
Route::put('/api/signalements/{id}', [SignalementController::class, 'update']);
Route::get('/api/signalements/stats', [SignalementController::class, 'stats']);

// Données référentielles
Route::get('/api/entreprises', [SignalementController::class, 'getEntreprises']);
Route::get('/api/type-signalements', [SignalementController::class, 'getTypeSignalements']);
Route::get('/api/type-statuts', [SignalementController::class, 'getTypeStatuts']);
Route::get('/api/roles', [SignalementController::class, 'getRoles']);

// Utilisateurs (gestion par le Manager)
Route::get('/api/utilisateurs', [SignalementController::class, 'getUtilisateurs']);
Route::post('/api/utilisateurs', [SignalementController::class, 'createUtilisateur']);
Route::put('/api/utilisateurs/{id}', [SignalementController::class, 'updateUtilisateur']);
Route::post('/api/utilisateurs/{id}/unblock', [SignalementController::class, 'unblockUtilisateur']);

// ==================== Vue principale (Manager Dashboard) ====================
Route::middleware('firebase.auth')->group(function () {
    Route::get('/map', function () {
        return view('map');
    })->name('map');
});

Route::get('/', function () {
    return view('welcome');
});

// ==================== Auth Firebase Web ====================
Route::get('/register', [FirebaseWebController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [FirebaseWebController::class, 'register'])->name('register.submit');

Route::get('/login', [FirebaseWebController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [FirebaseWebController::class, 'login'])->name('login.submit');

Route::middleware('firebase.auth')->group(function () {
    Route::get('/profile', [FirebaseWebController::class, 'profile'])->name('profile');
    Route::get('/profile/edit', [FirebaseWebController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [FirebaseWebController::class, 'update'])->name('profile.update');
    Route::post('/logout', [FirebaseWebController::class, 'logout'])->name('logout');
});

// Pages classiques
Route::get('/about', function () { return view('about'); });
