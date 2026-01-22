<?php

use Illuminate\Support\Facades\Route;
use App\Models\Category;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/hello', function () {
    return 'Bonjour Laravel!';
});

Route::get('/user/{name}', function ($name) {
    return "Bonjour, $name!";
});

Route::get('/user/{name?}', function ($name = 'Invité') {
    return "Bonjour, $name!";
});

// Route retournant une view
Route::get('/about', function () {
    return view('about');
});

Route::get('/contact', function () {
    $email = 'contact@example.com';
    return view('contact', ['email' => $email]);
});

Route::get('/users', function () {
    return view('users.index');
});
