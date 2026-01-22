<?php

namespace App\Http\Controllers;

use App\Models\Signalement;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function global(): JsonResponse
    {
        $signalements = Signalement::selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $total = $signalements->sum();
        $surface = Signalement::sum('surface_m2');
        $budget = Signalement::sum('budget');
        $completed = $signalements['termine'] ?? 0;
        $progress = $total > 0 ? round(($completed / $total) * 100, 2) : 0;

        return response()->json([
            'total_points' => $total,
            'surface_m2' => round($surface ?? 0, 2),
            'budget' => round($budget ?? 0, 2),
            'progress_percent' => $progress,
            'breakdown' => $signalements,
        ]);
    }
}
