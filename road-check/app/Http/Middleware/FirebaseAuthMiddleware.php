<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth;

class FirebaseAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1️⃣ Récupération robuste du token (header ou session)
        $authHeader =
            $request->header('Authorization')
            ?? $request->server('HTTP_AUTHORIZATION')
            ?? '';

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        } elseif (session()->has('firebase_id_token')) {
            $token = session('firebase_id_token');
        } else {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        try {
            // 2️⃣ Vérification Firebase
            $verifiedToken = app(Auth::class)->verifyIdToken($token);

            // 3️⃣ UID Firebase correct
            $request->attributes->set(
                'firebase_uid',
                $verifiedToken->claims()->get('sub')
            );

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Token invalide',
                'details' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
