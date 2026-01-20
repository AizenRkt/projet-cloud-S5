<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseWebController extends Controller
{
    protected FirebaseAuth $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }

    public function showRegisterForm() { return view('firebase.register'); }
    public function showLoginForm() { return view('firebase.login'); }

    public function register(Request $request)
    {
        $data = $request->validate([
            'email'=>'required|email',
            'password'=>'required|min:6',
            'nom'=>'required',
            'prenom'=>'required'
        ]);

        try {
            $firebaseUser = $this->auth->createUser([
                'email'=>$data['email'],
                'password'=>$data['password']
            ]);
            User::create([
                'email'=>$data['email'],
                'firebase_uid'=>$firebaseUser->uid,
                'nom'=>$data['nom'],
                'prenom'=>$data['prenom'],
                'id_role'=>1
            ]);

            return redirect()->route('login.form')->with('success','Inscription réussie !');

        } catch (\Exception $e) {
            return back()->withErrors(['error'=>$e->getMessage()]);
        }
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'=>'required|email',
            'password'=>'required|min:6'
        ]);

        try {
            $signIn = $this->auth->signInWithEmailAndPassword($data['email'],$data['password']);
            session(['firebase_id_token'=>$signIn->idToken()]);
            return redirect()->route('profile');
        } catch (\Exception $e) {
            return back()->withErrors(['error'=>'Email ou mot de passe invalide']);
        }
    }

    public function profile()
    {
        $token = session('firebase_id_token');
        return view('firebase.profile', compact('token'));
    }
}
