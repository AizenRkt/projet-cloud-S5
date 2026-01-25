<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

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
            'id_role' => 1, // rôle par défaut
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

        try {
            // Authentification via Firebase
            $signIn = $this->auth->signInWithEmailAndPassword($data['email'], $data['password']);
            $firebaseUser = $this->auth->getUserByEmail($data['email']);
        } catch (AuthException | FirebaseException $e) {
            return response()->json(['error' => 'Erreur Firebase: ' . $e->getMessage()], 400);
        }

        // Vérifier si l'utilisateur existe localement, sinon l'ajouter
        $utilisateur = Utilisateur::where('firebase_uid', $firebaseUser->uid)->first();
        if (!$utilisateur) {
            $utilisateur = Utilisateur::create([
                'email' => $firebaseUser->email,
                'firebase_uid' => $firebaseUser->uid,
                'nom' => $firebaseUser->displayName ?? '',
                'prenom' => '', // Firebase n'a pas prénom/nom séparés
                'id_role' => 1,
                'bloque' => false
            ]);
        }

        return response()->json([
            'idToken' => $signIn->idToken(),
            'refreshToken' => $signIn->refreshToken(),
            'user' => $utilisateur
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
        } catch (AuthException | FirebaseException $e) {
            return response()->json(['error' => 'Erreur Firebase lors de la vérification du token: ' . $e->getMessage()], 500);
        }

        // Mettre à jour Firebase si email fourni
        if ($request->filled('email')) {
            try {
                $this->auth->updateUser($uid, [
                    'email' => $request->email,
                ]);
            } catch (AuthException | FirebaseException $e) {
                return response()->json(['error' => 'Erreur Firebase lors de la mise à jour email: ' . $e->getMessage()], 400);
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
