<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Models\Utilisateur;
use App\Models\TentativeConnexion;

class LoginAttemptLimiter
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
