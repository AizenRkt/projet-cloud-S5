<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Signalement; 

class SignalementController extends Controller
{
    public function index()
    {
        return Signalement::select(
            'id_signalement',
            'latitude',
            'longitude',
            'statut',
            'surface_m2',
            'budget'
        )->get();
    }

    public function store(Request $request)
    {
        $signalement = Signalement::create([
            'latitude' => $request->lat,
            'longitude' => $request->lng,
            'surface_m2' => $request->surface_m2 ?? null,
            'budget' => $request->budget ?? null,
            'statut' => $request->statut ?? 'nouveau',
            'id_utilisateur' => 1, // default for testing
        ]);

        return response()->json($signalement);
    }

}
