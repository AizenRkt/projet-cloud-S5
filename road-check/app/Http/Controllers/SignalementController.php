<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Signalement;
use App\Models\SignalementStatus;
use App\Models\SignalementTypeStatus;
use App\Models\TypeSignalement;
use App\Models\Entreprise;
use App\Models\Utilisateur;
use App\Models\Role;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;

class SignalementController extends Controller
{
    protected FirebaseAuth $auth;

    public function __construct(FirebaseAuth $auth)
    {
        $this->auth = $auth;
    }
    // Liste des signalements avec relations
    public function index()
    {
        $signalements = Signalement::with(['typeSignalement', 'entreprise', 'utilisateur', 'dernierStatut.typeStatus', 'photos'])
            ->orderBy('date_signalement', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id_signalement' => $s->id_signalement,
                    'latitude' => $s->latitude,
                    'longitude' => $s->longitude,
                    'description' => $s->description,
                    'surface_m2' => $s->surface_m2,
                    'budget' => $s->budget,
                    'date_signalement' => $s->date_signalement,
                    'type_signalement' => $s->typeSignalement ? $s->typeSignalement->nom : null,
                    'id_type_signalement' => $s->id_type_signalement,
                    'entreprise' => $s->entreprise ? $s->entreprise->nom : null,
                    'id_entreprise' => $s->id_entreprise,
                    'utilisateur' => $s->utilisateur ? ($s->utilisateur->prenom . ' ' . $s->utilisateur->nom) : null,
                    'id_utilisateur' => $s->id_utilisateur,
                    'statut' => $s->dernierStatut && $s->dernierStatut->typeStatus ? $s->dernierStatut->typeStatus->code : 'nouveau',
                    'statut_libelle' => $s->dernierStatut && $s->dernierStatut->typeStatus ? $s->dernierStatut->typeStatus->libelle : 'Nouveau',
                    'pourcentage' => $s->dernierStatut && $s->dernierStatut->typeStatus ? $s->dernierStatut->typeStatus->pourcentage : 0,
                    'photos' => $s->photos->pluck('path')
                ];
            });

        return response()->json($signalements);
    }

    // Statistiques pour le tableau récap
    public function stats()
    {
        $signalements = Signalement::with('dernierStatut.typeStatus')->get();

        $total = $signalements->count();
        $totalSurface = $signalements->sum('surface_m2');
        $totalBudget = $signalements->sum('budget');

        $nouveau = 0;
        $enCours = 0;
        $termine = 0;
        $avancementTotal = 0;

        foreach ($signalements as $s) {
            $code = $s->dernierStatut && $s->dernierStatut->typeStatus
                ? $s->dernierStatut->typeStatus->code
                : 'nouveau';
            $pourcentage = $s->dernierStatut && $s->dernierStatut->typeStatus
                ? $s->dernierStatut->typeStatus->pourcentage
                : 0;

            if ($code === 'nouveau') $nouveau++;
            elseif ($code === 'en_cours') $enCours++;
            elseif ($code === 'termine') $termine++;

            $avancementTotal += $pourcentage;
        }

        return response()->json([
            'total' => $total,
            'nouveau' => $nouveau,
            'en_cours' => $enCours,
            'termine' => $termine,
            'total_surface' => $totalSurface,
            'total_budget' => $totalBudget,
            'avancement' => $total > 0 ? round($avancementTotal / $total, 2) : 0
        ]);
    }

    // Mise à jour d'un signalement (Manager)
    public function update(Request $request, $id)
    {
        $signalement = Signalement::findOrFail($id);

        // Update main fields
        $signalement->update([
            'description' => $request->description ?? $signalement->description,
            'surface_m2' => $request->surface_m2 ?? $signalement->surface_m2,
            'budget' => $request->budget ?? $signalement->budget,
            'id_entreprise' => $request->id_entreprise,
            'id_type_signalement' => $request->id_type_signalement ?? $signalement->id_type_signalement,
        ]);

        // Update status if provided
        if ($request->has('statut')) {
            $typeStatus = SignalementTypeStatus::where('code', $request->statut)->first();
            if ($typeStatus) {
                SignalementStatus::create([
                    'id_signalement' => $id,
                    'id_signalement_type_status' => $typeStatus->id_signalement_type_status,
                    'date_modification' => now()
                ]);
            }
        }

        return response()->json(['message' => 'Signalement mis à jour', 'data' => $signalement]);
    }

    // Liste des entreprises
    public function getEntreprises()
    {
        return response()->json(Entreprise::all());
    }

    // Liste des types de signalement
    public function getTypeSignalements()
    {
        return response()->json(TypeSignalement::all());
    }

    // Liste des statuts possibles
    public function getTypeStatuts()
    {
        return response()->json(SignalementTypeStatus::all());
    }

    // Liste des utilisateurs
    public function getUtilisateurs()
    {
        return response()->json(Utilisateur::with('role')->get()->map(function($u) {
            return [
                'id_utilisateur' => $u->id_utilisateur,
                'email' => $u->email,
                'firebase_uid' => $u->firebase_uid,
                'nom' => $u->nom,
                'prenom' => $u->prenom,
                'id_role' => $u->id_role,
                'role' => $u->role ? $u->role->nom : null,
                'bloque' => $u->bloque,
                'date_creation' => $u->date_creation
            ];
        }));
    }

    // Liste des rôles
    public function getRoles()
    {
        return response()->json(Role::all());
    }

    // Création d'un utilisateur (Manager)
    public function createUtilisateur(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:utilisateur,email',
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'password' => 'required|min:6',
            'id_role' => 'required|exists:role,id_role'
        ]);

        try {
            $utilisateur = Utilisateur::create([
                'email' => $request->email,
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'id_role' => $request->id_role,
                'firebase_uid' => 'local-' . uniqid(),
                'bloque' => false
            ]);

            return response()->json(['message' => 'Utilisateur créé', 'data' => $utilisateur], 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    // Mise à jour d'un utilisateur
    public function updateUtilisateur(Request $request, $id)
    {
        $utilisateur = Utilisateur::findOrFail($id);

        $utilisateur->update([
            'nom' => $request->nom ?? $utilisateur->nom,
            'prenom' => $request->prenom ?? $utilisateur->prenom,
            'id_role' => $request->id_role ?? $utilisateur->id_role,
            'bloque' => $request->has('bloque') ? $request->bloque : $utilisateur->bloque
        ]);

        return response()->json(['message' => 'Utilisateur mis à jour', 'data' => $utilisateur]);
    }

    // Débloquer un utilisateur
    public function unblockUtilisateur($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->update(['bloque' => false]);

        return response()->json(['message' => 'Utilisateur débloqué']);
    }
}
