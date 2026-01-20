<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FirebaseAuthController;



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
