<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;

class UnblockUserController extends Controller
{
    // POST /api/unblock
    public function unblock(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Récupérer l'utilisateur par email
        $utilisateur = Utilisateur::where('email', $request->email)->first();

        if (!$utilisateur) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Débloquer le compte
        $utilisateur->bloque = false;
        $utilisateur->save();

        // Supprimer les tentatives de connexion pour cet utilisateur
        DB::table('tentative_connexion')
            ->where('id_utilisateur', $utilisateur->id_utilisateur)
            ->delete();

        return response()->json([
            'message' => 'Utilisateur débloqué et tentatives réinitialisées',
            'email' => $utilisateur->email,
        ]);
    }
}
