<?php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $routeMiddleware = [
        // tes middlewares existants...
        'firebase.auth' => \App\Http\Middleware\FirebaseAuthMiddleware::class,
    ];
}
