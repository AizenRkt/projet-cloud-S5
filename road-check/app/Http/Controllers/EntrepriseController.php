<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\JsonResponse;

class EntrepriseController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Entreprise::orderBy('nom')->get());
    }
}
