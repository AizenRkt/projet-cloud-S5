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
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SignalementController extends Controller
{
    protected FirebaseAuth $auth;
    protected ?Firestore $firestore;

    public function __construct(FirebaseAuth $auth, ?Firestore $firestore = null)
    {
        $this->auth = $auth;
        $this->firestore = $firestore;
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
            'synced_to_firebase' => false, // Marquer pour re-synchronisation
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

    // Synchronisation des utilisateurs locaux vers Firebase
    public function syncUsersToFirebase()
    {
        try {
            $localUsers = Utilisateur::whereNull('firebase_uid')->orWhere('firebase_uid', 'like', 'local_%')->get(); // Utilisateurs pas encore sync ou locaux

            $syncedCount = 0;
            foreach ($localUsers as $localUser) {
                // Vérifier si déjà dans Firebase
                $firebaseUser = null;
                try {
                    $firebaseUser = $this->auth->getUserByEmail($localUser->email);
                } catch (\Exception $e) {
                    // Pas trouvé, on crée
                }

                if (!$firebaseUser) {
                    // Créer dans Firebase avec mot de passe local (doit être en clair)
                    $createdUser = $this->auth->createUser([
                        'email' => $localUser->email,
                        'password' => $localUser->password, // Assumé en clair
                        'displayName' => $localUser->nom . ' ' . $localUser->prenom,
                    ]);

                    // Mettre à jour firebase_uid en local
                    $localUser->update(['firebase_uid' => $createdUser->uid]);
                    $syncedCount++;
                }
            }

            return response()->json(['message' => $syncedCount . ' utilisateurs synchronisés vers Firebase']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    // ==================== SYNCHRONISATION SIGNALEMENTS -> FIREBASE ====================

    /**
     * Synchroniser les signalements locaux vers Firebase Firestore
     * POST /api/sync/to-firebase
     */
    public function syncSignalementsToFirebase(Request $request)
    {
        // Rate limiting: max 1 sync toutes les 30 secondes par utilisateur
        $utilisateur = session('utilisateur');
        $cacheKey = 'sync_rate_limit_' . ($utilisateur->id_utilisateur ?? 'anonymous');

        if (Cache::has($cacheKey)) {
            $remainingSeconds = Cache::get($cacheKey) - time();
            return response()->json([
                'success' => false,
                'message' => "Rate limit: veuillez attendre {$remainingSeconds} secondes avant la prochaine synchronisation",
                'synced' => [],
                'failed' => [],
                'timestamp' => now()->toIso8601String()
            ], 429);
        }

        // Vérifier que l'utilisateur est un manager (role_id = 1)
        if (!$utilisateur || $utilisateur->id_role !== 1) {
            // En mode développement, permettre l'accès pour les tests
            if (!app()->environment('local')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès refusé: seuls les managers peuvent synchroniser',
                    'synced' => [],
                    'failed' => [],
                    'timestamp' => now()->toIso8601String()
                ], 403);
            }
        }

        // Vérifier que Firestore est disponible
        if (!$this->firestore) {
            // En mode développement, permettre la simulation
            $simulate = config('app.env') === 'local' || env('FIREBASE_SIMULATE', false);

            if ($simulate) {
                // Récupérer les signalements pour la simulation
                $signalements = Signalement::where('synced_to_firebase', false)
                    ->with(['typeSignalement', 'entreprise', 'dernierStatut.typeStatus'])
                    ->get();
                return $this->simulateSyncSignalementsToFirebase($signalements);
            }

            return response()->json([
                'success' => false,
                'message' => 'Firebase Firestore n\'est pas configuré ou indisponible. Vérifiez : 1) Fichier credentials Firebase existe, 2) Firestore est activé dans votre projet Firebase, 3) Les permissions du service account sont correctes. Ou définissez FIREBASE_SIMULATE=true dans .env pour simuler.',
                'synced' => [],
                'failed' => [],
                'timestamp' => now()->toIso8601String()
            ], 503);
        }

        $synced = [];
        $failed = [];

        try {
            // Récupérer les signalements non synchronisés
            $signalements = Signalement::where('synced_to_firebase', false)
                ->with(['typeSignalement', 'entreprise', 'dernierStatut.typeStatus'])
                ->get();

            if ($signalements->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucun signalement à synchroniser',
                    'synced' => [],
                    'failed' => [],
                    'timestamp' => now()->toIso8601String()
                ]);
            }

            $database = $this->firestore->database();
            $collection = $database->collection('reports');

            foreach ($signalements as $signalement) {
                try {
                    // Préparer les données à synchroniser (champs modifiables uniquement)
                    $firebaseData = [
                        'statut' => $signalement->dernierStatut && $signalement->dernierStatut->typeStatus
                            ? $signalement->dernierStatut->typeStatus->code
                            : 'nouveau',
                        'statut_libelle' => $signalement->dernierStatut && $signalement->dernierStatut->typeStatus
                            ? $signalement->dernierStatut->typeStatus->libelle
                            : 'Nouveau',
                        'budget' => $signalement->budget,
                        'surface_m2' => $signalement->surface_m2,
                        'description' => $signalement->description,
                        'id_entreprise' => $signalement->id_entreprise,
                        'entreprise_nom' => $signalement->entreprise ? $signalement->entreprise->nom : null,
                        'type_signalement' => $signalement->typeSignalement ? $signalement->typeSignalement->nom : null,
                        'id_type_signalement' => $signalement->id_type_signalement,
                        'local_id' => $signalement->id_signalement,
                        'last_synced_at' => now()->toIso8601String(),
                        // Champs en lecture seule (pour référence, non modifiables côté Firebase)
                        'latitude' => $signalement->latitude,
                        'longitude' => $signalement->longitude,
                        'date_signalement' => $signalement->date_signalement,
                    ];

                    if ($signalement->firebase_id) {
                        // Mettre à jour le document existant
                        $docRef = $collection->document($signalement->firebase_id);
                        $docRef->set($firebaseData, ['merge' => true]);
                    } else {
                        // Créer un nouveau document
                        $newDoc = $collection->add($firebaseData);
                        $signalement->firebase_id = $newDoc->id();
                    }

                    // Marquer comme synchronisé
                    $signalement->synced_to_firebase = true;
                    $signalement->last_sync_attempt = now();
                    $signalement->sync_error = null;
                    $signalement->save();

                    $synced[] = (string) $signalement->id_signalement;

                } catch (\Exception $e) {
                    // Erreur pour ce signalement spécifique
                    Log::error("Erreur sync signalement #{$signalement->id_signalement}: " . $e->getMessage());

                    $signalement->last_sync_attempt = now();
                    $signalement->sync_error = substr($e->getMessage(), 0, 255);
                    $signalement->save();

                    $failed[] = (string) $signalement->id_signalement;
                }
            }

            // Appliquer le rate limit après une sync réussie
            Cache::put($cacheKey, time() + 30, 30);

            $success = count($failed) === 0;
            $message = count($synced) . ' signalement(s) synchronisé(s)';
            if (count($failed) > 0) {
                $message .= ', ' . count($failed) . ' en échec';
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'synced' => $synced,
                'failed' => $failed,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur globale sync Firebase: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur de synchronisation: ' . $e->getMessage(),
                'synced' => $synced,
                'failed' => $failed,
                'timestamp' => now()->toIso8601String()
            ], 500);
        }
    }

    /**
     * Simulation de synchronisation (pour développement sans Firebase)
     */
    private function simulateSyncSignalementsToFirebase($signalements)
    {
        $synced = [];
        $failed = [];

        Log::info('🔄 SIMULATION: Starting signalement sync to Firebase');

        foreach ($signalements as $signalement) {
            try {
                // Simuler un délai de traitement
                usleep(50000); // 50ms

                // Générer un ID Firebase fictif
                $firebaseId = 'sim_' . $signalement->id_signalement . '_' . time();

                // Marquer comme synchronisé
                $signalement->synced_to_firebase = true;
                $signalement->firebase_id = $firebaseId;
                $signalement->last_sync_attempt = now();
                $signalement->sync_error = null;
                $signalement->save();

                $synced[] = (string) $signalement->id_signalement;

                Log::info("✅ SIMULATION: Signalement #{$signalement->id_signalement} synced with Firebase ID: {$firebaseId}");

            } catch (\Exception $e) {
                Log::error("❌ SIMULATION: Failed to sync signalement #{$signalement->id_signalement}: " . $e->getMessage());

                $signalement->last_sync_attempt = now();
                $signalement->sync_error = substr($e->getMessage(), 0, 255);
                $signalement->save();

                $failed[] = (string) $signalement->id_signalement;
            }
        }

        $success = count($failed) === 0;
        $message = count($synced) . ' signalement(s) synchronisé(s) en mode SIMULATION';
        if (count($failed) > 0) {
            $message .= ', ' . count($failed) . ' en échec';
        }

        Log::info("🔄 SIMULATION: Sync completed - Success: {$success}, Synced: " . count($synced) . ", Failed: " . count($failed));

        return response()->json([
            'success' => $success,
            'message' => $message,
            'synced' => $synced,
            'failed' => $failed,
            'timestamp' => now()->toIso8601String(),
            'simulation' => true
        ]);
    }

    /**
     * Marquer un signalement pour re-synchronisation
     */
    public function markForResync($id)
    {
        $signalement = Signalement::findOrFail($id);
        $signalement->synced_to_firebase = false;
        $signalement->sync_error = null;
        $signalement->save();

        return response()->json(['message' => 'Signalement marqué pour re-synchronisation']);
    }

    /**
     * Endpoint de test pour la synchronisation (développement uniquement)
     */
    public function testSyncSignalementsToFirebase()
    {
        if (!app()->environment('local')) {
            return response()->json(['error' => 'Endpoint de test uniquement disponible en développement'], 403);
        }

        // Simuler une session utilisateur manager
        session(['utilisateur' => (object)['id_utilisateur' => 1, 'id_role' => 1, 'email' => 'admin@gmail.com']]);

        $request = new \Illuminate\Http\Request();
        $request->setMethod('POST');

        return $this->syncSignalementsToFirebase($request);
    }
    public function getSyncStatus()
    {
        $total = Signalement::count();
        $synced = Signalement::where('synced_to_firebase', true)->count();
        $pending = Signalement::where('synced_to_firebase', false)->count();
        $withErrors = Signalement::whereNotNull('sync_error')->count();

        return response()->json([
            'total' => $total,
            'synced' => $synced,
            'pending' => $pending,
            'with_errors' => $withErrors,
            'sync_percentage' => $total > 0 ? round(($synced / $total) * 100, 1) : 0
        ]);
    }
}
