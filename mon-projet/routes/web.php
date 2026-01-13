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


// Route pour lister les catégories avec recherche et pagination
//Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');


Route::get('/contact', function () {
    $email = 'contact@example.com';
    return view('contact', ['email' => $email]);
});


Route::resource('categories',CategoryController::class);
Route::resource('products', ProductController::class);
