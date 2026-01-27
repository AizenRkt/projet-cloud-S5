<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utilisateur;

/**
 * @OA\Info(
 *     title="API Road-Check",
 *     version="1.0.0"
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API server"
 * )
 *
 * @OA\Tag(
 *     name="Utilisateur",
 *     description="Endpoints pour gérer les utilisateurs"
 * )
 *
 * @OA\PathItem()
 */
class UnblockUserController extends Controller
{
    /**
     * Débloque un utilisateur
     *
     * @OA\Post(
     *     path="/unblock",
     *     summary="Débloque un utilisateur",
     *     tags={"Utilisateur"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateur débloqué"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Utilisateur non trouvé"
     *     )
     * )
     */
    public function unblock(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $utilisateur = Utilisateur::where('email', $request->email)->first();

        if (!$utilisateur) {
            return response()->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $utilisateur->unblock();

        return response()->json([
            'message' => 'Utilisateur débloqué et tentatives réinitialisées',
            'email' => $utilisateur->email,
        ]);
    }
}
