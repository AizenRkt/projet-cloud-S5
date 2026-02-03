<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\DB;

/**
 * Controller pour débloquer un utilisateur
 */
class UnblockUserController extends Controller
{
    /**
     * Débloque un utilisateur
     *
     */
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

        return redirect()->route('profile')->with('success', "Utilisateur débloqué : {$utilisateur->email}");
    }
}
