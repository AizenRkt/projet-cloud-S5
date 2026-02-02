<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Signalement;
use App\Models\ModificationSignalement;
use App\Models\Entreprise;
use App\Models\Utilisateur;

class SignalementController extends Controller
{
    public function index()
    {
        return Signalement::all();
    }

    public function getEntreprises()
    {
        return Entreprise::all();
    }

    public function getUtilisateurs()
    {
        return Utilisateur::all();
    }

    public function store(Request $request)
    {
        $signalement = Signalement::create([
            'latitude' => $request->lat,
            'longitude' => $request->lng,
            'surface_m2' => $request->surface_m2 ?? null,
            'budget' => $request->budget ?? null,
            'statut' => $request->statut ?? 'nouveau',
            'id_utilisateur' => $request->id_utilisateur ?? 1,
            'id_entreprise' => $request->id_entreprise ?? null,
        ]);

        return response()->json($signalement);
    }

    public function update(Request $request, $id)
    {
        $signalement = Signalement::findOrFail($id);

        // Update main record
        $signalement->update([
            'statut' => $request->statut,
            'budget' => $request->budget,
            'surface_m2' => $request->surface_m2,
            'id_entreprise' => $request->id_entreprise,
            'id_utilisateur' => $request->id_utilisateur,
        ]);

        // Create history record
        ModificationSignalement::create([
            'id_signalement' => $id,
            'id_utilisateur' => $request->id_utilisateur_modif ?? 1, // person doing the edit
            'statut' => $request->statut,
            'budget' => $request->budget,
            'surface_m2' => $request->surface_m2,
            'id_entreprise' => $request->id_entreprise,
            'note' => $request->note,
        ]);

        return response()->json($signalement);
    }
}
