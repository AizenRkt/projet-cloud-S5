<?php

namespace App\Http\Controllers;

use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalementController extends Controller
{
    public function index(): JsonResponse
    {
        $signalements = Signalement::with(['entreprise', 'utilisateur.role'])->latest('date_signalement')->get();
        return response()->json($signalements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_utilisateur' => 'nullable|integer|exists:utilisateur,id_utilisateur',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'statut' => 'in:nouveau,en cours,termine',
            'surface_m2' => 'nullable|numeric|min:0',
            'budget' => 'nullable|numeric|min:0',
            'id_entreprise' => 'nullable|integer|exists:entreprise,id_entreprise',
        ]);

        $signalement = Signalement::create($validated + ['statut' => $validated['statut'] ?? 'nouveau']);

        return response()->json($signalement->load(['entreprise', 'utilisateur']), 201);
    }

    public function update(Request $request, Signalement $signalement): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'sometimes|required|in:nouveau,en cours,termine',
            'surface_m2' => 'nullable|numeric|min:0',
            'budget' => 'nullable|numeric|min:0',
            'id_entreprise' => 'nullable|integer|exists:entreprise,id_entreprise',
        ]);

        $signalement->update($validated);

        return response()->json($signalement->load(['entreprise', 'utilisateur']));
    }
}
