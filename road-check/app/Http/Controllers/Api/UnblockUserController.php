<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;

class UnblockUserController extends Controller
{
    // POST /api/unblock
    public function unblock(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $utilisateur = Utilisateur::where('email', $request->email)->first();
        if (!$utilisateur) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }
        $utilisateur->bloque = false;
        $utilisateur->save();
        return response()->json(['message' => 'Utilisateur débloqué']);
    }
}
