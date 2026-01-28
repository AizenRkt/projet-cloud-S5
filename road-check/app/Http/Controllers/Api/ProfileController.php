<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Utilisateur;
use Illuminate\Http\Request;
use Kreait\Firebase\Auth as FirebaseAuth;

class ProfileController extends Controller
{
    public function __construct(private FirebaseAuth $auth)
    {
    }

    public function current(Request $request)
    {
        $firebaseUid = $request->attributes->get('firebase_uid');
        if (!$firebaseUid) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        $user = Utilisateur::with('role')->where('firebase_uid', $firebaseUid)->first();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        if ($user->bloque) {
            return response()->json(['error' => 'Compte bloqué'], 403);
        }

        return response()->json($this->mapUser($user));
    }

    public function index(Request $request)
    {
        $current = $this->resolveCurrent($request);
        if (!$current) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        if (!$this->isAdmin($current)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $users = Utilisateur::with('role')->get()->map(fn ($u) => $this->mapUser($u));
        return response()->json($users);
    }

    public function roles()
    {
        $roles = Role::all(['id_role', 'nom']);
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $current = $this->resolveCurrent($request);
        if (!$current) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        if (!$this->isAdmin($current)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $data = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email|unique:utilisateur,email',
            'password' => 'required|min:6',
            'id_role' => 'required|exists:role,id_role',
            'bloque' => 'sometimes|boolean',
        ]);

        try {
            $firebaseUser = $this->auth->createUser([
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user = Utilisateur::create([
                'email' => $data['email'],
                'firebase_uid' => $firebaseUser->uid,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'id_role' => $data['id_role'],
                'bloque' => $data['bloque'] ?? false,
            ]);

            return response()->json($this->mapUser($user), 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $current = $this->resolveCurrent($request);
        if (!$current) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        if (!$this->isAdmin($current)) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        if ($current->id_utilisateur === $id) {
            return response()->json(['error' => 'Impossible de supprimer votre propre compte'], 400);
        }

        $user = Utilisateur::find($id);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        $user->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function update(Request $request, int $id)
    {
        $current = $this->resolveCurrent($request);
        if (!$current) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        $user = Utilisateur::with('role')->find($id);
        if (!$user) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        $isAdmin = $this->isAdmin($current);
        $isSelf = $current->id_utilisateur === $user->id_utilisateur;

        if (!$isAdmin && !$isSelf) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $rules = [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'email' => 'required|email',
        ];

        if ($isAdmin) {
            $rules['id_role'] = 'required|exists:role,id_role';
            $rules['bloque'] = 'sometimes|boolean';
        }

        $data = $request->validate($rules);

        if ($data['email'] !== $user->email) {
            $this->auth->updateUser($user->firebase_uid, ['email' => $data['email']]);
        }

        $user->nom = $data['nom'];
        $user->prenom = $data['prenom'];
        $user->email = $data['email'];

        if ($isAdmin) {
            $user->id_role = $data['id_role'];
            if (array_key_exists('bloque', $data)) {
                $user->bloque = $data['bloque'];
            }
        }

        $user->save();
        $user->refresh();

        if ($isSelf) {
            session(['utilisateur' => $user]);
        }

        return response()->json($this->mapUser($user));
    }

    private function resolveCurrent(Request $request): ?Utilisateur
    {
        $firebaseUid = $request->attributes->get('firebase_uid');
        if (!$firebaseUid) {
            return null;
        }

        return Utilisateur::with('role')->where('firebase_uid', $firebaseUid)->first();
    }

    private function isAdmin(Utilisateur $user): bool
    {
        if (!$user->role) {
            return false;
        }

        $name = strtolower($user->role->nom);
        return in_array($name, ['administrateur', 'moderateur'], true);
    }

    private function mapUser(Utilisateur $user): array
    {
        return [
            'id' => $user->id_utilisateur,
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
            'role' => $user->role ? $user->role->nom : null,
            'id_role' => $user->id_role,
            'bloque' => (bool) $user->bloque,
        ];
    }
}
