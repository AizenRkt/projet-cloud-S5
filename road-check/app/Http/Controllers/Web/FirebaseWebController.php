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

            // Créer l'utilisateur local PostgreSQL
            Utilisateur::create([
                'email' => $data['email'],
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

        $utilisateur = Utilisateur::where('email', $data['email'])->first();
        $tentativeSucces = false;
        $limit = config('app.login_attempts_limit', 3);
        $minutesLimit = config('app.login_attempts_minutes', 1);

        if ($utilisateur) {
            // Récupérer toutes les tentatives échouées récentes
            $tentatives = \App\Models\TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)
                ->where('succes', false)
                ->orderBy('date_tentative', 'desc')
                ->get();

            // Vérifier si le compte est bloqué et si le temps écoulé depuis la dernière tentative a dépassé la limite
            if ($utilisateur->bloque && $tentatives->isNotEmpty()) {
                $derniereTentative = Carbon::parse($tentatives->first()->date_tentative)->setTimezone('UTC');
                $now = Carbon::now('UTC');
                $tempsEcoule = $derniereTentative->diffInMinutes($now);

                if ($tempsEcoule >= $minutesLimit) {
                    // Débloquer le compte et supprimer les anciennes tentatives
                    $utilisateur->bloque = false;
                    $utilisateur->save();
                    \App\Models\TentativeConnexion::where('id_utilisateur', $utilisateur->id_utilisateur)->delete();
                } else {
                    return back()->withErrors([
                        'error' => "Compte bloqué. Réessayez dans " . ($minutesLimit - $tempsEcoule) . " minute(s)."
                    ]);
                }
            }
        }

        try {
            $signIn = $this->auth->signInWithEmailAndPassword($data['email'], $data['password']);
            $firebaseUser = $this->auth->getUserByEmail($data['email']);

            // Vérifier si l'utilisateur existe localement, sinon l'ajouter
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

            // Stocker le token et l'utilisateur complet en session
            session([
                'firebase_id_token' => $signIn->idToken(),
                'utilisateur' => $utilisateur
            ]);
            $tentativeSucces = true;

        } catch (AuthException | FirebaseException $e) {
            // Gestion Firebase
        } catch (\Exception $e) {
            // Gestion autres erreurs
        }

        // Enregistrer la tentative (échec ou succès)
        if ($utilisateur) {
            \App\Models\TentativeConnexion::create([
                'id_utilisateur' => $utilisateur->id_utilisateur,
                'date_tentative' => Carbon::now('UTC'),
                'succes' => $tentativeSucces
            ]);
        }

        if ($tentativeSucces) {
            return redirect()->route('profile');
        } else {
            return back()->withErrors(['error' => 'Email ou mot de passe invalide']);
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
