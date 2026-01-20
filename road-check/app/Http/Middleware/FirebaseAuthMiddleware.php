<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Kreait\Firebase\Auth;

class FirebaseAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        try {
            $verified = app(Auth::class)->verifyIdToken($token);
            $request->attributes->set('firebase_uid', $verified->uid);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erreur serveur'], 500);
        }

        return $next($request);
    }
}
