<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use App\Models\TentativeConnexion;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class FirebaseAuthController extends Controller
{
    protected FirebaseAuth $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }

    // 🔹 INSCRIPTION
    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
            'nom' => 'required|string',
            'prenom' => 'required|string'
        ]);

        // 1️⃣ Création utilisateur Firebase
        try {
            $firebaseUser = $this->auth->createUser([
                'email' => $data['email'],
                'password' => $data['password']
            ]);
        } catch (AuthException | FirebaseException $e) {
            return response()->json(['error' => 'Erreur Firebase lors de la création: ' . $e->getMessage()], 400);
        }

        // 2️⃣ Création utilisateur local PostgreSQL
        $utilisateur = Utilisateur::create([
            'email' => $data['email'],
            'firebase_uid' => $firebaseUser->uid,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'id_role' => 1,
            'bloque' => false
        ]);

        return response()->json($utilisateur, 201);
    }

    // 🔹 LOGIN
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $limit = Config::get('app.login_attempts_limit', 3);
        $minutesLimit = Config::get('app.login_attempts_minutes', 5);
        $now = Carbon::now('UTC');

        // Vérifier si le compte est bloqué
        $utilisateur = Utilisateur::where('email', $data['email'])->first();

        if ($utilisateur && $utilisateur->bloque) {
            // Vérifier si le temps de blocage est expiré
            $derniereTentative = TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('succes', false)
                ->orderBy('date_tentative', 'desc')
                ->first();

            if ($derniereTentative) {
                $dateTentative = Carbon::parse($derniereTentative->date_tentative)->setTimezone('UTC');
                if ($dateTentative->diffInMinutes($now) < $minutesLimit) {
                    return response()->json(['error' => 'Compte bloqué. Réessayez plus tard.'], 423);
                } else {
                    // Auto-déblocage si le temps est passé
                    $utilisateur->bloque = false;
                    $utilisateur->save();
                }
            }
        }

        try {
            // Authentification via Firebase
            $signIn = $this->auth->signInWithEmailAndPassword($data['email'], $data['password']);
            $firebaseUser = $this->auth->getUserByEmail($data['email']);

            // Si authentification réussie, on gère l'utilisateur local
            $utilisateur = Utilisateur::where('firebase_uid', $firebaseUser->uid)->first();

            // Création si n'existe pas
            if (!$utilisateur) {
                $utilisateur = Utilisateur::create([
                    'email' => $firebaseUser->email,
                    'firebase_uid' => $firebaseUser->uid,
                    'nom' => $firebaseUser->displayName ?? '',
                    'prenom' => '',
                    'id_role' => 1,
                    'bloque' => false
                ]);
            }

            // Enregistrer tentative réussie
            TentativeConnexion::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'succes' => true,
                'date_tentative' => $now
            ]);

            // Réinitialiser le blocage au cas où
            if ($utilisateur->bloque) {
                $utilisateur->bloque = false;
                $utilisateur->save();
            }

            return response()->json([
                'idToken' => $signIn->idToken(),
                'refreshToken' => $signIn->refreshToken(),
                'user' => $utilisateur
            ]);

        } catch (AuthException | FirebaseException $e) {
            // Enregistrer tentative échouée si l'utilisateur existe
            if ($utilisateur) {
                TentativeConnexion::create([
                    'id_utilisateur' => $utilisateur->id_utilisateur,
                    'succes' => false,
                    'date_tentative' => $now
                ]);

                // Compter les échecs récents
                $failures = TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
                    ->where('succes', false)
                    ->where('date_tentative', '>=', $now->copy()->subMinutes($minutesLimit))
                    ->count();

                if ($failures >= $limit) {
                    $utilisateur->bloque = true;
                    $utilisateur->save();
                    return response()->json(['error' => 'Compte bloqué. Trop de tentatives.'], 423);
                }
            }

            return response()->json(['error' => 'Email ou mot de passe incorrect.'], 401);
        }
    }

    // 🔹 UPDATE PROFIL
    public function update(Request $request)
    {
        $request->validate([
            'email' => 'sometimes|email',
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string'
        ]);

        $token = $request->bearerToken();

        try {
            $verifiedToken = $this->auth->verifyIdToken($token);
            $uid = $verifiedToken->uid;
        } catch (AuthException | FirebaseException $e) {
            return response()->json(['error' => 'Token invalide.'], 401);
        }

        // Mettre à jour Firebase si email fourni
        if ($request->filled('email')) {
            try {
                $this->auth->updateUser($uid, [
                    'email' => $request->email,
                ]);
            } catch (AuthException | FirebaseException $e) {
                return response()->json(['error' => 'Erreur lors de la mise à jour email.'], 400);
            }
        }

        // Mettre à jour PostgreSQL local
        $utilisateur = Utilisateur::where('firebase_uid', $uid)->first();
        if ($utilisateur) {
            $utilisateur->update($request->only('nom', 'prenom'));
        }

        return response()->json(['message' => 'Profil mis à jour']);
    }
}
