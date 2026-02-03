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
            return redirect()->route('profile')->with('success', 'Connecté en mode local');
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

    // 🔹 TRAITEMENT MODIFICATION
    public function update(Request $request)
    {
        $utilisateur = session('utilisateur');
        if (!$utilisateur) {
            return redirect()->route('login.form')->withErrors(['error' => 'Veuillez vous connecter']);
        }

        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email',
            'id_role' => 'required|exists:role,id_role'
        ]);

        try {
            // MAJ PostgreSQL uniquement
            $utilisateur->nom = $data['nom'];
            $utilisateur->prenom = $data['prenom'];
            $utilisateur->email = $data['email'];
            $utilisateur->id_role = $data['id_role'];
            $utilisateur->save();

            // MAJ session
            session(['utilisateur' => $utilisateur]);

            return redirect()->route('profile')->with('success', 'Profil mis à jour !');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


}
