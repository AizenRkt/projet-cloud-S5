<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

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

        try {
            $signIn = $this->auth->signInWithEmailAndPassword($data['email'], $data['password']);
            $firebaseUser = $this->auth->getUserByEmail($data['email']);

            // Vérifier si l'utilisateur existe localement, sinon l'ajouter
            $utilisateur = Utilisateur::where('firebase_uid', $firebaseUser->uid)->first();
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


            return redirect()->route('profile');

        } catch (AuthException | FirebaseException $e) {
            return back()->withErrors(['error' => 'Erreur Firebase : ' . $e->getMessage()]);
        } catch (\Exception $e) {
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

        return view('firebase.profile', [
            'token' => $token,
            'prenom' => $utilisateur->prenom,
            'nom' => $utilisateur->nom
        ]);
    }


}
