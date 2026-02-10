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

    // 🔹 Afficher formulaire de login avec test Firestore (Kreait)
    public function showLoginForm()
    {
        $firestoreStatus = null;
        try {
            $factory = (new \Kreait\Firebase\Factory())
                ->withServiceAccount(config('services.firebase.credentials'))
                ->withDatabaseUri('https://road-check-a6a4a-default-rtdb.europe-west1.firebasedatabase.app');
            $database = $factory->createDatabase();
            // Test simple : lire une clé bidon (Firestore RTDB, pas Firestore v2)
            $snapshot = $database->getReference('test-connexion')->getSnapshot();
            $firestoreStatus = $snapshot->exists() ? 'Connexion Firestore RTDB OK' : 'Connexion Firestore RTDB vide';
        } catch (\Throwable $e) {
            $firestoreStatus = 'Erreur Firestore (Kreait) : ' . $e->getMessage();
        }
        return view('firebase.login', compact('firestoreStatus'));
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

        // Vérification locale uniquement (supporte mot de passe haché ou en clair)
        $passwordMatch = false;
        if ($utilisateur && !$utilisateur->bloque && !empty($utilisateur->password)) {
            // Essayer d'abord avec Hash::check (mot de passe haché)
            if (Hash::check($data['password'], $utilisateur->password)) {
                $passwordMatch = true;
            }
            // Sinon comparer en clair (fallback)
            elseif ($data['password'] === $utilisateur->password) {
                $passwordMatch = true;
            }
        }
        if ($passwordMatch) {
            // Stocker en session sans JWT
            session([
                'utilisateur' => $utilisateur,
                'is_logged_in' => true
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
            return redirect('/map')->with('success', 'Connecté localement');
        } else {
            return redirect()->route('login.form')->withErrors(['error' => 'Email ou mot de passe invalide']);
        }
    }

    // 🔹 PROFIL
    public function profile()
    {
        // Récupérer l'utilisateur depuis la session
        $utilisateur = session('utilisateur');
        if (!$utilisateur) {
            return redirect()->route('login.form')->withErrors(['error' => 'Veuillez vous connecter']);
        }
        $role = Role::find($utilisateur->id_role);
        return view('firebase.profile', [
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
        session()->forget(['utilisateur', 'is_logged_in']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login.form')->with('success', 'Déconnecté');
    }
}
