<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth;

class FirebaseAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 1️⃣ Vérifier si l'utilisateur est déjà authentifié via Laravel Auth (pour login local)
        if (auth()->check()) {
            $request->attributes->set('firebase_uid', auth()->id());
            return $next($request);
        }

        // 1️⃣bis Vérifier si l'utilisateur est connecté via session locale (login sans JWT)
        if (session()->has('utilisateur')) {
            $utilisateur = session('utilisateur');
            $request->attributes->set('firebase_uid', $utilisateur->firebase_uid ?? $utilisateur->id_utilisateur ?? null);
            return $next($request);
        }

        // 2️⃣ Récupération robuste du token (header ou session)
        $authHeader =
            $request->header('Authorization')
            ?? $request->server('HTTP_AUTHORIZATION')
            ?? '';

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        } elseif (session()->has('firebase_id_token')) {
            $token = session('firebase_id_token');
        } else {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Token manquant'], 401);
            }

            return redirect()->route('login.form');
        }

        // 2️⃣ Détection du type de token (Firebase ou local)
        $isFirebaseToken = false;
        if (is_string($token) && strlen($token) > 100 && strpos($token, '.') !== false) {
            // On suppose que les tokens Firebase sont plus longs et contiennent des claims spécifiques
            // On tente de décoder l'en-tête JWT pour voir si c'est un token Firebase
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
                if (isset($header['kid']) || (isset($header['alg']) && $header['alg'] === 'RS256')) {
                    $isFirebaseToken = true;
                }
            }
        }

        if ($isFirebaseToken) {
            try {
                // Vérification Firebase
                $verifiedToken = app(Auth::class)->verifyIdToken($token);
                $request->attributes->set(
                    'firebase_uid',
                    method_exists($verifiedToken, 'getClaim') ? $verifiedToken->getClaim('sub') : ($verifiedToken->claims()['sub'] ?? null)
                );
            } catch (\Throwable $e) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Token invalide',
                        'details' => $e->getMessage()
                    ], 401);
                }

                return redirect()->route('login.form');
            }
        } else {
            // Token local offline : décodage avec la clé d'app
            try {
                $key = env('APP_KEY');
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
                // On vérifie l'expiration
                if (isset($decoded->exp) && $decoded->exp < time()) {
                    if ($request->expectsJson()) {
                        return response()->json(['error' => 'Token expiré (offline)'], 401);
                    }

                    return redirect()->route('login.form');
                }
                // On simule l'attribut firebase_uid avec l'id utilisateur
                $request->attributes->set('firebase_uid', $decoded->sub ?? null);
            } catch (\Throwable $e) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Token offline invalide',
                        'details' => $e->getMessage()
                    ], 401);
                }

                return redirect()->route('login.form');
            }
        }

        return $next($request);
    }
}
