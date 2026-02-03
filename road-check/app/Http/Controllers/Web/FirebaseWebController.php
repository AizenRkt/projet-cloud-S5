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
            'email' => 'required|email',
            'password' => 'required|min:6',
            'nom' => 'required|string',
            'prenom' => 'required|string'
        ]);

        try {
            // Créer l'utilisateur dans Firebase
            $firebaseUser = $this->auth->createUser([
                'email' => $data['email'],
                'password' => $data['password']
            ]);

            // Créer l'utilisateur local PostgreSQL avec mot de passe en clair (non sécurisé)
            Utilisateur::create([
                'email' => $data['email'],
                'password' => $data['password'],
                'firebase_uid' => $firebaseUser->uid,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'id_role' => 1,
                'bloque' => false
            ]);

            return redirect()->route('login.form')->with('success', 'Inscription réussie !');

        } catch (AuthException | FirebaseException $e) {
            return back()->withErrors(['error' => 'Erreur Firebase : ' . $e->getMessage()]);
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

        // Test de connexion réseau (ping Google DNS)
        $hasNetwork = false;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = @shell_exec('ping -n 1 8.8.8.8');
            $hasNetwork = (strpos($output, 'TTL=') !== false);
        } else {
            $output = @shell_exec('ping -c 1 8.8.8.8');
            $hasNetwork = (strpos($output, 'ttl=') !== false);
        }

        if (!$hasNetwork) {
            // Pas de réseau : fallback local direct
            if ($utilisateur && !$utilisateur->bloque && !empty($utilisateur->password) && $data['password'] === $utilisateur->password) {
                $jwtToken = $this->generateLocalJwt($utilisateur);
                session([
                    'firebase_id_token' => $jwtToken,
                    'utilisateur' => $utilisateur
                ]);
                $tentativeSucces = true;
            }
        } else {
            try {
                // Essayer Firebase Auth
                $signIn = $this->auth->signInWithEmailAndPassword(
                    $data['email'],
                    $data['password']
                );

                $firebaseUser = $this->auth->getUserByEmail($data['email']);

                if (!$utilisateur) {
                    $utilisateur = Utilisateur::create([
                        'email' => $firebaseUser->email,
                        'password' => $data['password'],
                        'firebase_uid' => $firebaseUser->uid,
                        'nom' => $firebaseUser->displayName ?? '',
                        'prenom' => '',
                        'id_role' => 2,
                        'bloque' => false
                    ]);
                } elseif (empty($utilisateur->password)) {
                    // Si l'utilisateur existait sans password (migration), on le met à jour
                    $utilisateur->password = $data['password'];
                    $utilisateur->save();
                }

                session([
                    'firebase_id_token' => $signIn->idToken(),
                    'utilisateur' => $utilisateur
                ]);

                $tentativeSucces = true;

            } catch (\Kreait\Firebase\Exception\AuthException | \Kreait\Firebase\Exception\FirebaseException $e) {
                // Si erreur Firebase liée à la connexion réseau, fallback local
                if (strpos($e->getMessage(), 'network') !== false || strpos($e->getMessage(), 'Network') !== false || strpos($e->getMessage(), 'connect') !== false) {
                    // Vérification locale
                    if ($utilisateur && !$utilisateur->bloque && !empty($utilisateur->password) && $data['password'] === $utilisateur->password) {
                        // Générer un JWT local
                        $jwtToken = $this->generateLocalJwt($utilisateur);
                        session([
                            'firebase_id_token' => $jwtToken,
                            'utilisateur' => $utilisateur
                        ]);
                        $tentativeSucces = true;
                    }
                }
            } catch (\Exception $e) {
                // Autres erreurs : on ignore pour la logique de tentative
            }
        }

        // ❌ On n'enregistre que les échecs
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

        // 🔓 Auto-unblock si succès ET utilisateur pas bloqué
        if ($tentativeSucces && $utilisateur && !$utilisateur->bloque) {
            $utilisateur->unblock();
        }

        if ($tentativeSucces) {
            return redirect()->route('profile')->with('success', $jwtToken ? 'Connecté en mode offline (JWT local)' : 'Connecté via Firebase');
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
            // MAJ Firebase (email seulement)
            if ($data['email'] !== $utilisateur->email) {
                $this->auth->updateUser($utilisateur->firebase_uid, [
                    'email' => $data['email']
                ]);
            }

            // MAJ PostgreSQL
            $utilisateur->nom = $data['nom'];
            $utilisateur->prenom = $data['prenom'];
            $utilisateur->email = $data['email'];
            $utilisateur->id_role = $data['id_role'];
            $utilisateur->save();

            // MAJ session
            session(['utilisateur' => $utilisateur]);

            return redirect()->route('profile')->with('success', 'Profil mis à jour !');
        } catch (AuthException | FirebaseException $e) {
            return back()->withErrors(['error' => 'Erreur Firebase : ' . $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }


}
