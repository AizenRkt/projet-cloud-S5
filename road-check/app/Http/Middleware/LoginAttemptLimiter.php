<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Models\Utilisateur;
use App\Models\TentativeConnexion;
use Carbon\Carbon;

class LoginAttemptLimiter
{
    public function handle(Request $request, Closure $next)
    {
        $email = $request->input('email');
        if (!$email) return $next($request);

        $limit = Config::get('app.login_attempts_limit', 3);
        $minutesLimit = Config::get('app.login_attempts_minutes', 1);

        $utilisateur = Utilisateur::where('email', $email)->first();
        if (!$utilisateur) return $next($request);

        // Récupérer les tentatives échouées
        $tentatives = TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('succes', false)
            ->orderBy('date_tentative', 'desc')
            ->get();

        // Si le compte est bloqué, vérifier si le temps est écoulé
        if ($utilisateur->bloque) {
            if ($tentatives->isNotEmpty()) {
                $derniereTentative = $tentatives->first();
                // forcer la timezone UTC pour la comparaison
                $dateTentative = Carbon::parse($derniereTentative->date_tentative)->setTimezone('UTC');
                $now = Carbon::now('UTC');

                $tempsEcoule = $dateTentative->diffInMinutes($now);

                if ($tempsEcoule >= $minutesLimit) {
                    // Débloquer le compte et supprimer les anciennes tentatives
                    $utilisateur->bloque = false;
                    $utilisateur->save();

                    TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)->delete();
                } else {
                    return response()->json([
                        'error' => "Compte bloqué. Réessayez dans " . ($minutesLimit - $tempsEcoule) . " minute(s)."
                    ], 423);
                }
            } else {
                // Si bloqué mais pas de tentative (au cas où)
                $utilisateur->bloque = false;
                $utilisateur->save();
            }
        }

        // Compter les tentatives échouées récentes après éventuel débloquage
        $tentativesCount = TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
            ->where('succes', false)
            ->where('date_tentative', '>=', Carbon::now('UTC')->subMinutes($minutesLimit))
            ->count();

        // Bloquer si limite dépassée
        if ($tentativesCount >= $limit) {
            $utilisateur->bloque = true;
            $utilisateur->save();

            return response()->json([
                'error' => "Trop de tentatives. Compte bloqué pour $minutesLimit minute(s)."
            ], 423);
        }

        // Passer la requête
        $response = $next($request);

        // Enregistrer la tentative
        TentativeConnexion::create([
            'id_utilisateur' => $utilisateur->id_utilisateur,
            'date_tentative' => Carbon::now('UTC'),
            'succes' => $response->status() < 400
        ]);

        return $response;
    }
}
