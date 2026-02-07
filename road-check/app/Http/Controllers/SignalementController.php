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
use App\Models\TentativeConnexion;
use App\Models\PhotoSignalement;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Firestore;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

    // Débloquer un utilisateur (PG + Firestore)
    public function unblockUtilisateur($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->update(['bloque' => false]);

        // Synchroniser le déblocage vers Firestore
        try {
            Http::timeout(5)->post('http://firestore-sync:4000/update-user-bloque', [
                'email' => $utilisateur->email,
                'bloque' => false,
            ]);
        } catch (\Exception $e) {
            Log::warning('Sync Firestore déblocage échouée: ' . $e->getMessage());
        }

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

    // ==================== SYNCHRONISATION BIDIRECTIONNELLE ====================

    /**
     * Synchronisation bidirectionnelle PostgreSQL ↔ Firestore
     * Ordre: entreprises → types_signalement → utilisateurs → signalements → tentatives_connexion
     * POST /api/sync/bidirectional
     */
    public function syncBidirectional(Request $request)
    {
        $resultsPgToFs = [
            'roles' => 0,
            'entreprises' => 0,
            'types_signalement' => 0,
            'utilisateurs' => 0,
            'signalements' => 0,
            'tentatives_connexion' => 0,
        ];
        $resultsFsToPg = [
            'roles' => ['inserted' => 0, 'updated' => 0, 'errors' => []],
            'entreprises' => ['inserted' => 0, 'updated' => 0, 'errors' => []],
            'types_signalement' => ['inserted' => 0, 'updated' => 0, 'errors' => []],
            'utilisateurs' => ['inserted' => 0, 'updated' => 0, 'errors' => []],
            'signalements' => ['inserted' => 0, 'updated' => 0, 'errors' => []],
            'tentatives_connexion' => ['inserted' => 0, 'updated' => 0, 'errors' => []],
        ];

        try {
            // =============================================
            // ÉTAPE 1 : PostgreSQL → Firestore
            // Ordre: entreprises → types_signalement → utilisateurs → signalements → tentatives_connexion
            // =============================================

            $sessionUser = session('utilisateur');

            // Préparer toutes les données PG dans le bon ordre
            // 0. Roles (nouveau - nécessaire pour les utilisateurs)
            $roles = Role::all()->map(fn($r) => [
                'id_role' => $r->id_role,
                'nom' => $r->nom,
            ])->toArray();

            $entreprises = Entreprise::all()->map(fn($e) => [
                'id_entreprise' => $e->id_entreprise,
                'nom' => $e->nom,
                'logo' => $e->logo ?? null,
            ])->toArray();

            $typesSignalement = TypeSignalement::all()->map(fn($ts) => [
                'id_type_signalement' => $ts->id_type_signalement,
                'nom' => $ts->nom,
                'icon' => $ts->icon ?? null,
            ])->toArray();

            $utilisateurs = Utilisateur::with('role')->get()->map(fn($u) => [
                'id_utilisateur' => $u->id_utilisateur,
                'email' => $u->email,
                'nom' => $u->nom,
                'prenom' => $u->prenom,
                'firebase_uid' => $u->firebase_uid,
                'role' => $u->role ? $u->role->nom : 'Utilisateur',
                'id_role' => $u->id_role,
                'bloque' => $u->bloque,
                'date_creation' => $u->date_creation,
            ])->toArray();

            $signalements = Signalement::with(['typeSignalement', 'entreprise', 'utilisateur', 'dernierStatut.typeStatus', 'photos'])
                ->get()
                ->map(function ($s) use ($sessionUser) {
                    $email = $s->utilisateur ? $s->utilisateur->email : ($sessionUser ? $sessionUser->email : null);
                    $uid = $s->utilisateur ? $s->utilisateur->firebase_uid : ($sessionUser ? $sessionUser->firebase_uid : null);
                    return [
                        'local_id' => $s->id_signalement,
                        'firebase_id' => $s->firebase_id,
                        'budget' => $s->budget,
                        'date_signalement' => $s->date_signalement,
                        'date_status' => $s->dernierStatut ? $s->dernierStatut->date_modification : $s->date_signalement,
                        'description' => $s->description,
                        'id_entreprise' => $s->id_entreprise,
                        'entreprise_nom' => $s->entreprise ? $s->entreprise->nom : null,
                        'latitude' => $s->latitude,
                        'longitude' => $s->longitude,
                        'photos' => $s->photos ? $s->photos->pluck('path')->toArray() : [],
                        'statut' => $s->dernierStatut && $s->dernierStatut->typeStatus ? $s->dernierStatut->typeStatus->code : 'nouveau',
                        'surface_m2' => $s->surface_m2,
                        'id_type_signalement' => $s->id_type_signalement,
                        'type_signalement' => $s->typeSignalement ? $s->typeSignalement->nom : null,
                        'utilisateur_email' => $email,
                        'utilisateur_id' => $uid,
                    ];
                })->toArray();

            $tentatives = TentativeConnexion::all()->map(function ($tc) {
                $user = Utilisateur::find($tc->id_utilisateur);
                return [
                    'id_tentative' => $tc->id_tentative,
                    'id_utilisateur' => $tc->id_utilisateur,
                    'utilisateur_email' => $user ? $user->email : null,
                    'date_tentative' => $tc->date_tentative,
                    'succes' => $tc->succes,
                ];
            })->toArray();

            // Envoyer tout au microservice Node.js (PG → Firestore)
            $pgToFsResponse = Http::timeout(120)->post('http://firestore-sync:4000/sync-all-to-firestore', [
                'roles' => $roles,
                'entreprises' => $entreprises,
                'types_signalement' => $typesSignalement,
                'utilisateurs' => $utilisateurs,
                'signalements' => $signalements,
                'tentatives_connexion' => $tentatives,
            ]);

            if ($pgToFsResponse->successful()) {
                $pgResult = $pgToFsResponse->json()['results'] ?? [];
                $resultsPgToFs = [
                    'roles' => $pgResult['roles']['synced'] ?? 0,
                    'entreprises' => $pgResult['entreprises']['synced'] ?? 0,
                    'types_signalement' => $pgResult['types_signalement']['synced'] ?? 0,
                    'utilisateurs' => $pgResult['utilisateurs']['synced'] ?? 0,
                    'signalements' => $pgResult['signalements']['synced'] ?? 0,
                    'tentatives_connexion' => $pgResult['tentatives_connexion']['synced'] ?? 0,
                ];

                // Marquer les signalements comme synchronisés
                $syncedIds = $pgResult['synced_ids'] ?? [];
                if (!empty($syncedIds)) {
                    Signalement::whereIn('id_signalement', $syncedIds)
                        ->update([
                            'synced_to_firebase' => true,
                            'last_sync_attempt' => now(),
                            'sync_error' => null,
                        ]);
                }
            }

            // =============================================
            // ÉTAPE 2 : Firestore → PostgreSQL
            // Même ordre: entreprises → types_signalement → utilisateurs → signalements → tentatives_connexion
            // =============================================

            $fsResponse = Http::timeout(120)->get('http://firestore-sync:4000/get-all-collections');

            if ($fsResponse->successful()) {
                $firestoreData = $fsResponse->json()['data'] ?? [];

                // 0. Roles (AVANT les utilisateurs !)
                foreach ($firestoreData['roles'] ?? [] as $doc) {
                    try {
                        $existing = Role::where('nom', $doc['nom'] ?? '')->first();
                        if ($existing) {
                            $resultsFsToPg['roles']['updated']++;
                        } else {
                            Role::create(['nom' => $doc['nom'] ?? 'Utilisateur']);
                            $resultsFsToPg['roles']['inserted']++;
                        }
                    } catch (\Exception $e) {
                        $resultsFsToPg['roles']['errors'][] = $e->getMessage();
                    }
                }

                // 1. Entreprises
                foreach ($firestoreData['entreprises'] ?? [] as $doc) {
                    try {
                        $existing = Entreprise::where('nom', $doc['nom'] ?? '')->first();
                        if ($existing) {
                            $existing->update(['nom' => $doc['nom'] ?? $existing->nom, 'logo' => $doc['logo'] ?? $existing->logo]);
                            $resultsFsToPg['entreprises']['updated']++;
                        } else {
                            Entreprise::create(['nom' => $doc['nom'] ?? 'Sans nom', 'logo' => $doc['logo'] ?? null]);
                            $resultsFsToPg['entreprises']['inserted']++;
                        }
                    } catch (\Exception $e) {
                        $resultsFsToPg['entreprises']['errors'][] = $e->getMessage();
                    }
                }

                // 2. Types signalement
                foreach ($firestoreData['types_signalement'] ?? [] as $doc) {
                    try {
                        $existing = TypeSignalement::where('nom', $doc['nom'] ?? '')->first();
                        if ($existing) {
                            $existing->update(['nom' => $doc['nom'] ?? $existing->nom, 'icon' => $doc['icon'] ?? $existing->icon]);
                            $resultsFsToPg['types_signalement']['updated']++;
                        } else {
                            TypeSignalement::create(['nom' => $doc['nom'] ?? 'Sans nom', 'icon' => $doc['icon'] ?? null]);
                            $resultsFsToPg['types_signalement']['inserted']++;
                        }
                    } catch (\Exception $e) {
                        $resultsFsToPg['types_signalement']['errors'][] = $e->getMessage();
                    }
                }

                // 3. Utilisateurs (AVANT les signalements pour que les FK soient valides)
                foreach ($firestoreData['utilisateurs'] ?? [] as $doc) {
                    try {
                        $email = $doc['email'] ?? $doc['utilisateurEmail'] ?? null;
                        if (!$email) continue;

                        // Chercher par email OU par firebase_uid
                        $existing = Utilisateur::where('email', $email)->first();
                        if (!$existing && !empty($doc['firebase_uid'])) {
                            $existing = Utilisateur::where('firebase_uid', $doc['firebase_uid'])->first();
                        }

                        if ($existing) {
                            // Ne mettre à jour que les champs non-vides de Firestore
                            $updateData = [];
                            if (!empty($doc['nom'])) {
                                $updateData['nom'] = $doc['nom'];
                            }
                            if (!empty($doc['prenom'])) {
                                $updateData['prenom'] = $doc['prenom'];
                            }
                            // firebase_uid: préférer la valeur non-null, vérifier unicité
                            if (!empty($doc['firebase_uid']) && $doc['firebase_uid'] !== $existing->firebase_uid) {
                                // Vérifier qu'aucun autre utilisateur n'a déjà ce firebase_uid
                                $uidConflict = Utilisateur::where('firebase_uid', $doc['firebase_uid'])
                                    ->where('id_utilisateur', '!=', $existing->id_utilisateur)->first();
                                if (!$uidConflict) {
                                    $updateData['firebase_uid'] = $doc['firebase_uid'];
                                }
                            }
                            // bloque: toujours synchroniser (logique OR déjà appliquée dans Node.js)
                            if (isset($doc['bloque'])) {
                                $updateData['bloque'] = $doc['bloque'];
                            }
                            // id_role: synchroniser si présent et valide (vérifier FK role)
                            if (isset($doc['id_role']) && (int)$doc['id_role'] > 0) {
                                if (Role::where('id_role', (int)$doc['id_role'])->exists()) {
                                    $updateData['id_role'] = (int)$doc['id_role'];
                                }
                            }
                            if (!empty($updateData)) {
                                $existing->update($updateData);
                            }
                            $resultsFsToPg['utilisateurs']['updated']++;
                        } else {
                            // Résoudre le rôle avec validation FK
                            $roleId = 3; // Default: Utilisateur
                            if (isset($doc['role'])) {
                                $role = Role::where('nom', $doc['role'])->first();
                                if ($role) $roleId = $role->id_role;
                            } elseif (isset($doc['id_role']) && (int)$doc['id_role'] > 0) {
                                if (Role::where('id_role', (int)$doc['id_role'])->exists()) {
                                    $roleId = (int) $doc['id_role'];
                                }
                            }
                            // Fallback: vérifier que le rôle 3 existe, sinon prendre le premier
                            if (!Role::where('id_role', $roleId)->exists()) {
                                $firstRole = Role::first();
                                $roleId = $firstRole ? $firstRole->id_role : 3;
                            }

                            // Générer un firebase_uid unique
                            $firebaseUid = $doc['firebase_uid'] ?? null;
                            if ($firebaseUid) {
                                // Vérifier unicité
                                if (Utilisateur::where('firebase_uid', $firebaseUid)->exists()) {
                                    $firebaseUid = $firebaseUid . '-' . uniqid();
                                }
                            } else {
                                $firebaseUid = 'firestore-' . uniqid();
                            }

                            Utilisateur::create([
                                'email' => $email,
                                'password' => \Illuminate\Support\Facades\Hash::make('firebase_user'),
                                'firebase_uid' => $firebaseUid,
                                'nom' => $doc['nom'] ?? '',
                                'prenom' => $doc['prenom'] ?? '',
                                'id_role' => $roleId,
                                'bloque' => $doc['bloque'] ?? false,
                            ]);
                            $resultsFsToPg['utilisateurs']['inserted']++;
                        }
                    } catch (\Exception $e) {
                        $resultsFsToPg['utilisateurs']['errors'][] = ($doc['email'] ?? 'unknown') . ': ' . $e->getMessage();
                    }
                }

                // 4. Signalements
                foreach ($firestoreData['signalements'] ?? [] as $doc) {
                    try {
                        $firestoreId = $doc['firestore_id'] ?? null;
                        $existing = $firestoreId ? Signalement::where('firebase_id', $firestoreId)->first() : null;

                        // Résoudre type_signalement avec validation FK
                        $typeSignalementId = null;
                        if (isset($doc['typeSignalementId'])) {
                            $candidateId = (int) $doc['typeSignalementId'];
                            // Vérifier que le type existe dans PG
                            if (TypeSignalement::where('id_type_signalement', $candidateId)->exists()) {
                                $typeSignalementId = $candidateId;
                            }
                        }
                        if (!$typeSignalementId && isset($doc['typeSignalementNom'])) {
                            $ts = TypeSignalement::where('nom', $doc['typeSignalementNom'])->first();
                            $typeSignalementId = $ts ? $ts->id_type_signalement : null;
                        }
                        // Fallback: premier type existant dans PG
                        if (!$typeSignalementId) {
                            $firstType = TypeSignalement::first();
                            $typeSignalementId = $firstType ? $firstType->id_type_signalement : 1;
                        }

                        // Résoudre entreprise avec validation FK
                        $entrepriseId = null;
                        if (isset($doc['entrepriseId']) && $doc['entrepriseId']) {
                            $candidateId = (int) $doc['entrepriseId'];
                            if (Entreprise::where('id_entreprise', $candidateId)->exists()) {
                                $entrepriseId = $candidateId;
                            }
                        }
                        if (!$entrepriseId && isset($doc['entrepriseNom']) && $doc['entrepriseNom']) {
                            $ent = Entreprise::where('nom', $doc['entrepriseNom'])->first();
                            $entrepriseId = $ent ? $ent->id_entreprise : null;
                        }

                        // Résoudre utilisateur avec validation FK
                        $utilisateurId = null;
                        if (isset($doc['utilisateurId']) && $doc['utilisateurId']) {
                            $user = Utilisateur::where('firebase_uid', $doc['utilisateurId'])->first();
                            $utilisateurId = $user ? $user->id_utilisateur : null;
                        }
                        if (!$utilisateurId && isset($doc['utilisateurEmail']) && $doc['utilisateurEmail']) {
                            $user = Utilisateur::where('email', $doc['utilisateurEmail'])->first();
                            $utilisateurId = $user ? $user->id_utilisateur : null;
                        }
                        // Auto-créer l'utilisateur s'il n'existe pas dans PG
                        if (!$utilisateurId && (isset($doc['utilisateurEmail']) && $doc['utilisateurEmail'])) {
                            try {
                                $defaultRoleId = 3;
                                if (!Role::where('id_role', $defaultRoleId)->exists()) {
                                    $firstRole = Role::first();
                                    $defaultRoleId = $firstRole ? $firstRole->id_role : 3;
                                }
                                $firebaseUid = $doc['utilisateurId'] ?? ('firestore-auto-' . uniqid());
                                // Vérifier unicité firebase_uid
                                if (Utilisateur::where('firebase_uid', $firebaseUid)->exists()) {
                                    $firebaseUid = $firebaseUid . '-' . uniqid();
                                }
                                $newUser = Utilisateur::create([
                                    'email' => $doc['utilisateurEmail'],
                                    'password' => \Illuminate\Support\Facades\Hash::make('firebase_user'),
                                    'firebase_uid' => $firebaseUid,
                                    'nom' => '', 'prenom' => '',
                                    'id_role' => $defaultRoleId,
                                    'bloque' => false,
                                ]);
                                $utilisateurId = $newUser->id_utilisateur;
                                Log::info("Auto-created user {$doc['utilisateurEmail']} (id={$utilisateurId}) for signalement");
                            } catch (\Exception $userEx) {
                                Log::warning("Could not auto-create user {$doc['utilisateurEmail']}: " . $userEx->getMessage());
                            }
                        }

                        $signalementData = [
                            'id_type_signalement' => $typeSignalementId,
                            'id_entreprise' => $entrepriseId,
                            'latitude' => $doc['latitude'] ?? 0, 'longitude' => $doc['longitude'] ?? 0,
                            'description' => $doc['description'] ?? '',
                            'surface_m2' => $doc['surface'] ?? $doc['surface_m2'] ?? 0,
                            'budget' => $doc['budget'] ?? 0,
                            'date_signalement' => isset($doc['dateSignalement']) ? date('Y-m-d H:i:s', strtotime($doc['dateSignalement'])) : now(),
                            'synced_to_firebase' => true, 'firebase_id' => $firestoreId,
                            'last_sync_attempt' => now(), 'sync_error' => null,
                        ];

                        if ($existing) {
                            $existing->update($signalementData);

                            // Sync photos: fusionner PG existantes + Firestore
                            $photosArray = $doc['photos'] ?? [];
                            if (is_array($photosArray) && count($photosArray) > 0) {
                                // Récupérer les photos PG existantes
                                $existingPhotos = PhotoSignalement::where('id_signalement', $existing->id_signalement)
                                    ->pluck('path')->toArray();
                                // Insérer les photos Firestore qui n'existent pas déjà dans PG
                                foreach ($photosArray as $photoPath) {
                                    if ($photoPath && is_string($photoPath) && !in_array($photoPath, $existingPhotos)) {
                                        PhotoSignalement::create([
                                            'id_signalement' => $existing->id_signalement,
                                            'path' => $photoPath,
                                        ]);
                                    }
                                }
                            }

                            $resultsFsToPg['signalements']['updated']++;
                        } else {
                            // Seulement pour les nouveaux signalements: assigner l'utilisateur
                            $signalementData['id_utilisateur'] = $utilisateurId;
                            $sig = Signalement::create($signalementData);
                            $statusCode = $doc['status'] ?? 'nouveau';
                            $typeStatus = SignalementTypeStatus::where('code', $statusCode)->first();
                            if ($typeStatus) {
                                SignalementStatus::create([
                                    'id_signalement' => $sig->id_signalement,
                                    'id_signalement_type_status' => $typeStatus->id_signalement_type_status,
                                    'date_modification' => isset($doc['dateStatus']) ? date('Y-m-d H:i:s', strtotime($doc['dateStatus'])) : now(),
                                ]);
                            }

                            // Sync photos pour le nouveau signalement
                            $photosArray = $doc['photos'] ?? [];
                            if (is_array($photosArray)) {
                                foreach ($photosArray as $photoPath) {
                                    if ($photoPath && is_string($photoPath)) {
                                        PhotoSignalement::create([
                                            'id_signalement' => $sig->id_signalement,
                                            'path' => $photoPath,
                                        ]);
                                    }
                                }
                            }

                            $resultsFsToPg['signalements']['inserted']++;
                        }
                    } catch (\Exception $e) {
                        $resultsFsToPg['signalements']['errors'][] = ($doc['firestore_id'] ?? 'unknown') . ': ' . $e->getMessage();
                    }
                }

                // 5. Tentatives connexion (champs Firestore: email, success, timestamp)
                foreach ($firestoreData['tentatives_connexion'] ?? [] as $doc) {
                    try {
                        // Résoudre l'utilisateur par email
                        $utilisateurId = null;
                        $email = $doc['email'] ?? $doc['utilisateurEmail'] ?? null;
                        if (isset($doc['id_utilisateur'])) {
                            $utilisateurId = (int) $doc['id_utilisateur'];
                        } elseif ($email) {
                            $user = Utilisateur::where('email', $email)->first();
                            $utilisateurId = $user ? $user->id_utilisateur : null;
                        }

                        // Résoudre la date: champ timestamp (ms) ou dateTentative ou date_tentative
                        if (isset($doc['timestamp'])) {
                            $dateTentative = date('Y-m-d H:i:s', intval($doc['timestamp'] / 1000));
                        } elseif (isset($doc['dateTentative'])) {
                            $dateTentative = date('Y-m-d H:i:s', strtotime($doc['dateTentative']));
                        } elseif (isset($doc['date_tentative'])) {
                            $dateTentative = date('Y-m-d H:i:s', strtotime($doc['date_tentative']));
                        } else {
                            $dateTentative = now();
                        }

                        // Résoudre le succès: champ success ou succes
                        $succes = $doc['success'] ?? $doc['succes'] ?? false;

                        $existing = TentativeConnexion::where('id_utilisateur', $utilisateurId)
                            ->where('date_tentative', $dateTentative)->first();

                        if ($existing) {
                            $existing->update(['succes' => $succes]);
                            $resultsFsToPg['tentatives_connexion']['updated']++;
                        } else {
                            TentativeConnexion::create([
                                'id_utilisateur' => $utilisateurId,
                                'date_tentative' => $dateTentative,
                                'succes' => $succes,
                            ]);
                            $resultsFsToPg['tentatives_connexion']['inserted']++;
                        }
                    } catch (\Exception $e) {
                        $resultsFsToPg['tentatives_connexion']['errors'][] = $e->getMessage();
                    }
                }
            }

            // =============================================
            // RÉSUMÉ
            // =============================================
            $totalPgToFs = array_sum($resultsPgToFs);
            $totalInserted = array_sum(array_column($resultsFsToPg, 'inserted'));
            $totalUpdated = array_sum(array_column($resultsFsToPg, 'updated'));
            $totalErrors = array_sum(array_map(fn($r) => count($r['errors']), $resultsFsToPg));

            return response()->json([
                'success' => $totalErrors === 0,
                'message' => "PG→Firestore: {$totalPgToFs} envoyé(s) | Firestore→PG: {$totalInserted} inséré(s), {$totalUpdated} mis à jour"
                    . ($totalErrors > 0 ? " | {$totalErrors} erreur(s)" : ''),
                'pg_to_firestore' => $resultsPgToFs,
                'firestore_to_pg' => $resultsFsToPg,
                'timestamp' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur sync bidirectionnelle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur de synchronisation: ' . $e->getMessage(),
                'pg_to_firestore' => $resultsPgToFs,
                'firestore_to_pg' => $resultsFsToPg,
                'timestamp' => now()->toIso8601String(),
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
    public function testSyncBidirectional()
    {
        if (!app()->environment('local')) {
            return response()->json(['error' => 'Endpoint de test uniquement disponible en développement'], 403);
        }

        session(['utilisateur' => (object)['id_utilisateur' => 1, 'id_role' => 1, 'email' => 'admin@gmail.com']]);

        $request = new \Illuminate\Http\Request();
        $request->setMethod('POST');

        return $this->syncBidirectional($request);
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
