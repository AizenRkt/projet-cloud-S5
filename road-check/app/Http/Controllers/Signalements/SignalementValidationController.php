<?php

namespace App\Http\Controllers\Signalements;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Signalement;
use App\Models\SignalementStatus;
use App\Models\SignalementTypeStatus;
use App\Models\ModificationSignalement;

class SignalementValidationController extends Controller
{
    public function validateSignalement(Request $request, $id)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000'
        ]);

        $sessionUser = session('utilisateur');
        if (!$sessionUser || empty($sessionUser->id_utilisateur)) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        if ((int) $sessionUser->id_role !== 1) {
            return response()->json(['message' => 'Accès refusé: rôle manager requis'], 403);
        }

        $signalement = Signalement::with('dernierStatut.typeStatus')->find($id);
        if (!$signalement) {
            return response()->json(['message' => 'Signalement introuvable'], 404);
        }

        $currentStatusCode = $signalement->dernierStatut && $signalement->dernierStatut->typeStatus
            ? $signalement->dernierStatut->typeStatus->code
            : 'nouveau';

        if ($currentStatusCode !== 'nouveau') {
            return response()->json(['message' => 'Validation impossible: le signalement est déjà traité'], 409);
        }

        $typeStatus = SignalementTypeStatus::where('code', 'en_cours')->first();
        if (!$typeStatus) {
            return response()->json(['message' => 'Statut de validation introuvable'], 500);
        }

        SignalementStatus::create([
            'id_signalement' => $signalement->id_signalement,
            'id_signalement_type_status' => $typeStatus->id_signalement_type_status,
            'date_modification' => now()
        ]);

        ModificationSignalement::create([
            'id_signalement' => $signalement->id_signalement,
            'id_utilisateur' => $sessionUser->id_utilisateur,
            'statut' => $typeStatus->code,
            'budget' => $signalement->budget,
            'surface_m2' => $signalement->surface_m2,
            'id_entreprise' => $signalement->id_entreprise,
            'note' => $request->note,
            'date_modification' => now()
        ]);

        $signalement->update([
            'synced_to_firebase' => false
        ]);

        return response()->json([
            'message' => 'Signalement validé',
            'data' => [
                'id_signalement' => $signalement->id_signalement,
                'statut' => $typeStatus->code,
                'statut_libelle' => $typeStatus->libelle
            ]
        ]);
    }
}
