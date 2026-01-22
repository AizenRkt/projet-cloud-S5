<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with('role')->get();
        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('role'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:utilisateur,email',
            'firebase_uid' => 'required|string|unique:utilisateur,firebase_uid',
            'nom' => 'nullable|string|max:100',
            'prenom' => 'nullable|string|max:100',
            'id_role' => 'required|integer|exists:role,id_role',
            'bloque' => 'boolean',
        ]);

        $user = User::create($validated);
        return response()->json($user->load('role'), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:utilisateur,email,' . $user->id_utilisateur . ',id_utilisateur',
            'nom' => 'nullable|string|max:100',
            'prenom' => 'nullable|string|max:100',
            'id_role' => 'required|integer|exists:role,id_role',
            'bloque' => 'boolean',
        ]);

        $user->update($validated);
        return response()->json($user->load('role'));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }
}
