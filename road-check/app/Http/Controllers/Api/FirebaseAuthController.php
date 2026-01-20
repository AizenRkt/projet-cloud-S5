<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;
use Kreait\Firebase\Exception\AuthException;

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
        $firebaseUser = $this->auth->createUser([
            'email' => $data['email'],
            'password' => $data['password']
        ]);

        // 2️⃣ Création utilisateur local
        $user = User::create([
            'email' => $data['email'],
            'firebase_uid' => $firebaseUser->uid,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'id_role' => 1 // rôle par défaut
        ]);

        return response()->json($user, 201);
    }

    // 🔹 LOGIN
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // ⚡ Authentification via Firebase REST
        try {
            $signIn = $this->auth->signInWithEmailAndPassword($data['email'], $data['password']);
        } catch (\Kreait\Firebase\Exception\AuthException $e) {
            return response()->json(['error' => 'Erreur Firebase: '.$e->getMessage()], 400);
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            return response()->json(['error' => 'Erreur Firebase'], 500);
        }

        return response()->json([
            'idToken' => $signIn->idToken(),
            'refreshToken' => $signIn->refreshToken()
        ]);
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
        }catch (AuthException $e) {
            return response()->json(['error' => 'Erreur Firebase'], 500);
        }

        // Mettre à jour Firebase si email fourni
        if ($request->filled('email')) {
            $this->auth->updateUser($uid, [
                'email' => $request->email,
            ]);
        }

        // Mettre à jour PostgreSQL local
        $user = User::where('firebase_uid', $uid)->first();
        if ($user) {
            $user->update($request->only('nom', 'prenom'));
        }

        return response()->json(['message' => 'Profil mis à jour']);
    }
}
