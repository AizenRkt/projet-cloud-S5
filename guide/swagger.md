# Intégration Swagger/OpenAPI dans le projet RoadCheck

## Qu'est-ce que Swagger/OpenAPI ?

Swagger (maintenant OpenAPI) est une spécification pour décrire les APIs REST de manière standardisée. Il permet de :
- Documenter automatiquement les endpoints, paramètres, réponses, schémas de données
- Générer une interface interactive (Swagger UI) pour tester les APIs en direct
- Valider les requêtes/réponses selon les schémas définis
- Générer du code client/serveur dans plusieurs langages

Dans ce projet, Swagger sert à documenter l'API complète avec les modules Auth, Sync, Users, Reports, facilitant le développement et les tests.

## Comment intégrer Swagger dans Laravel avec l5-swagger

### 1. Installation du package
Le package `darkaonline/l5-swagger` est déjà installé (voir composer.json).

### 2. Configuration
- Fichier de config : `config/l5-swagger.php`
- Modifications apportées :
  - `'format_to_use_for_docs' => 'yaml'` : Utilise YAML au lieu de JSON
  - `'generate' => false` : Désactive la génération automatique depuis les annotations PHP
  - `'annotations' => []` : Vide pour éviter le scan des contrôleurs

### 3. Fichier de spécification
- Source : `swagger.yaml` (à la racine du projet road-check)
- Copié vers : `storage/api-docs/api-docs.yaml`
- Contient la spec OpenAPI 3.0.0 complète avec :
  - Endpoints pour Auth (login avec fallback offline), Sync, Users, Reports
  - Schémas de données (User, Report, SyncPayload, etc.)
  - Sécurité Bearer JWT
  - Exemples de requêtes/réponses

### 4. Accès à la documentation
- Route : `/api/documentation`
- Interface interactive pour tester les endpoints
- Accessible après `php artisan serve` sur `http://localhost:8000/api/documentation`

### 5. Modules documentés

#### Authentification
- POST /auth/login : Login avec fallback offline (si pas de réseau, utilise PostgreSQL local)
- POST /auth/refresh : Rafraîchir token Firebase
- POST /auth/logout : Déconnexion

#### Synchronisation
- POST /sync/push : Pousser données locales vers Firebase/Postgres
- GET /sync/pull : Récupérer données distantes

#### Gestion utilisateurs
- POST /users/{userId}/unblock : Débloquer un utilisateur
- POST /users/{userId}/reset-password : Reset mot de passe

#### Signalements routiers
- GET /reports : Lister signalements
- POST /reports : Créer signalement
- GET /reports/{id} : Détail
- PUT /reports/{id} : Modifier
- DELETE /reports/{id} : Supprimer

### 6. Sécurité
- Bearer JWT pour tous les endpoints protégés
- Support des tokens Firebase (online) et locaux (offline)

### 7. Schémas de données
- User : id, email, role, blocked, createdAt
- Report : id, userId, latitude, longitude, status, surface_m2, budget, createdAt
- SyncPayload : lastSync, reports[]
- LoginRequest : email, password
- AuthResponse : accessToken, refreshToken, mode (online/offline)

### 8. Validation et tests
- Utilise les schémas pour valider les payloads
- Interface Swagger UI permet de tester en direct sans Postman
- Réponses standardisées (200, 400, 401, 500)

## Avantages pour le développement
- Documentation toujours à jour et interactive
- Tests faciles des endpoints
- Réduction des erreurs de communication API
- Génération possible de clients SDK

## Maintenance
- Modifier `swagger.yaml` pour ajouter/modifier endpoints
- Recopier vers `storage/api-docs/api-docs.yaml`
- Vider cache config si besoin : `php artisan config:clear`