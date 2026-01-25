<?php
// Ajoute ce middleware dans Kernel.php pour l'utiliser sur les routes d'auth
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
        $limit = Config::get('app.login_attempts_limit', 3);
        $utilisateur = Utilisateur::where('email', $email)->first();
        if ($utilisateur && $utilisateur->bloque) {
            return response()->json(['error' => 'Compte bloqué.'], 423);
        }
        // Compter les tentatives échouées sur les 30 dernières minutes
        $tentatives = 0;
        if ($utilisateur) {
            $tentatives = TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('succes', false)
                ->where('date_tentative', '>=', now()->subMinutes(30))
                ->count();
        }
        if ($tentatives >= $limit) {
            if ($utilisateur) {
                $utilisateur->bloque = true;
                $utilisateur->save();
            }
            return response()->json(['error' => 'Trop de tentatives. Compte bloqué.'], 423);
        }
        $response = $next($request);
        // Enregistrer la tentative
        if ($utilisateur) {
            TentativeConnexion::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'date_tentative' => now(),
                'succes' => !($response->status() === 401 || $response->status() === 400)
            ]);
        }
        return $response;
    }
}
