<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

use App\Models\Role;
use App\Models\TentativeConnexion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;


class FirebaseWebController extends Controller
{
    protected FirebaseAuth $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }

    // 🔹 Afficher formulaire d'inscription
    public function showRegisterForm()
    {
        return view('firebase.register');
    }

    // 🔹 Afficher formulaire de login
    public function showLoginForm()
    {
        return view('firebase.login');
    }

    // 🔹 INSCRIPTION
    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|unique:utilisateur,email',
            'password' => 'required|min:6',
            'nom' => 'required|string',
            'prenom' => 'required|string'
        ]);

        try {
            // Générer un UID fictif pour simuler Firebase
            $fakeUid = 'local_' . uniqid();

            // Créer l'utilisateur local PostgreSQL
            Utilisateur::create([
                'email' => $data['email'],
                'password' => $data['password'], // Stockage en clair pour simplicité locale
                'firebase_uid' => $fakeUid,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'id_role' => 2, // Utilisateur par défaut
                'bloque' => false
            ]);

            return redirect()->route('login.form')->with('success', 'Inscription réussie !');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // 🔹 LOGIN
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        $limit = config('app.login_attempts_limit', 1);
        $utilisateur = Utilisateur::where('email', $data['email'])->first();

        if ($utilisateur && $utilisateur->bloque) {
            return back()->withErrors([
                'error' => 'Compte bloqué. Contactez un administrateur.'
            ]);
        }

        $tentativeSucces = false;
        $jwtToken = null;

        // Vérification locale uniquement
        if ($utilisateur && !$utilisateur->bloque && !empty($utilisateur->password) && $data['password'] === $utilisateur->password) {
            // Générer un JWT local
            $jwtToken = $this->generateLocalJwt($utilisateur);
            session([
                'firebase_id_token' => $jwtToken,
                'utilisateur' => $utilisateur
            ]);
            $tentativeSucces = true;
        }

        // Enregistrer les tentatives
        if (!$tentativeSucces && $utilisateur) {
            $nbTentatives = \App\Models\TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('succes', false)
                ->count();

            \App\Models\TentativeConnexion::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'date_tentative' => now(),
                'succes' => false
            ]);

            if ($nbTentatives + 1 >= $limit) {
                $utilisateur->bloque = true;
                $utilisateur->save();

                return back()->withErrors([
                    'error' => 'Tentative échouée. Compte bloqué.'
                ]);
            }
        }

        // Auto-unblock si succès
        if ($tentativeSucces && $utilisateur && !$utilisateur->bloque) {
            $utilisateur->unblock();
        }

        if ($tentativeSucces) {
            return redirect()->route('map')->with('success', 'Connecté en mode local');
        } else {
            return back()->withErrors(['error' => 'Email ou mot de passe invalide']);
        }
    }

    /**
     * Génère un JWT local pour l'utilisateur (fallback offline)
     */
    protected function generateLocalJwt($utilisateur)
    {
        // Utilise lcobucci/jwt ou firebase/php-jwt (ici version simple)
        $key = env('APP_KEY');
        $payload = [
            'sub' => $utilisateur->id_utilisateur,
            'email' => $utilisateur->email,
            'iat' => time(),
            'exp' => time() + 3600, // 1h
        ];
        return \Firebase\JWT\JWT::encode($payload, $key, 'HS256');
    }


    // 🔹 PROFIL
    public function profile()
    {
        // Récupérer l'utilisateur depuis la session
        $utilisateur = session('utilisateur');
        if (!$utilisateur) {
            return redirect()->route('login.form')->withErrors(['error' => 'Veuillez vous connecter']);
        }
        $token = session('firebase_id_token');
        $role = Role::find($utilisateur->id_role);
        return view('firebase.profile', [
            'token' => $token,
            'prenom' => $utilisateur->prenom,
            'nom' => $utilisateur->nom,
            'role' => $role ? $role->nom : ''
        ]);
    }
        // 🔹 FORMULAIRE MODIFICATION
    public function edit()
    {
        $utilisateur = session('utilisateur');
        if (!$utilisateur) {
            return redirect()->route('login.form')->withErrors(['error' => 'Veuillez vous connecter']);
        }
        $roles = Role::all();
        return view('firebase.edit', [
            'utilisateur' => $utilisateur,
            'roles' => $roles
        ]);
    }

    // 🔹 LOGOUT
    public function logout(Request $request)
    {
        // Clear session
        session()->forget(['firebase_id_token', 'utilisateur']);

        return redirect()->route('login.form')->with('success', 'Déconnecté');
    }

    // 🔹 SYNCHRONISATION UTILISATEURS LOCAL -> FIREBASE
    public function syncUsersToFirebase()
    {
        try {
            $localUsers = Utilisateur::all(); // Récupérer tous les utilisateurs locaux
            $syncedCount = 0;

            foreach ($localUsers as $localUser) {
                // Vérifier si l'utilisateur existe déjà dans Firebase (par email)
                $firebaseUser = null;
                try {
                    $firebaseUser = $this->auth->getUserByEmail($localUser->email);
                } catch (\Exception $e) {
                    // Utilisateur n'existe pas, on le crée
                }

                if (!$firebaseUser) {
                    // Créer dans Firebase avec le mot de passe local (supposé en clair)
                    $createdUser = $this->auth->createUser([
                        'email' => $localUser->email,
                        'password' => $localUser->password, // Doit être en clair
                        'displayName' => $localUser->nom . ' ' . $localUser->prenom,
                    ]);

                    // Mettre à jour le firebase_uid en local pour lier
                    $localUser->update(['firebase_uid' => $createdUser->uid]);
                    $syncedCount++;
                } else {
                    // Utilisateur existe, mettre à jour displayName si nécessaire
                    $this->auth->updateUser($firebaseUser->uid, [
                        'displayName' => $localUser->nom . ' ' . $localUser->prenom,
                    ]);
                }
            }

            return response()->json(['message' => $syncedCount . ' utilisateur(s) synchronisé(s) vers Firebase.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }
}
