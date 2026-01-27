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
        $email = $request->input('email');
        if (!$email) {
            return $next($request);
        }

        $limit = Config::get('app.login_attempts_limit', 1);        // max tentatives
        $minutesLimit = Config::get('app.login_attempts_minutes', 10); // durée en minutes pour test

        $utilisateur = Utilisateur::where('email', $email)->first();

        if (!$utilisateur) {
            return $next($request);
        }

        // Supprimer les tentatives dépassant la durée limite
        TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('date_tentative', '<', now()->subMinutes($minutesLimit))
            ->delete();

        // Recompter les tentatives échouées restantes
        $tentatives = TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('succes', false)
            ->count();

        if ($tentatives >= $limit) {
            $utilisateur->bloque = true;
            $utilisateur->save();

            return response()->json(['error' => 'Trop de tentatives. Compte bloqué.'], 423);
        }

        $response = $next($request);

        // Enregistrer la tentative
        TentativeConnexion::create([
            'id_utilisateur' => $utilisateur->id_utilisateur,
            'date_tentative' => now(),
            'succes' => $response->status() < 400
        ]);

        // Si succès et compte était bloqué, débloquer automatiquement
        if ($response->status() < 400 && $utilisateur->bloque) {
            $utilisateur->bloque = false;
            $utilisateur->save();

            // Optionnel : supprimer les anciennes tentatives
            TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)->delete();
        }

        return $response;
    }
}
