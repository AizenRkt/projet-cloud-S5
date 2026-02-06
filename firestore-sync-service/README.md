# Firestore Sync Service

Microservice Node.js pour synchroniser les signalements dans Firestore (Firebase) depuis Laravel.

## Installation

1. Placez le fichier `firebase_credentials.json` dans ce dossier (déjà fait).
2. Installez les dépendances :
   ```bash
   npm install
   ```
3. Lancez le service :
   ```bash
   npm start
   ```

Le service écoute par défaut sur le port 4000.

## API

### POST /sync-signalements
Synchronise un tableau de signalements dans Firestore (collection `reports`).

- **URL** : `http://localhost:4000/sync-signalements`
- **Méthode** : POST
- **Corps JSON** :
  ```json
  {
    "signalements": [
      { "local_id": 1, "description": "...", ... },
      ...
    ]
  }
  ```
- **Réponse** :
  ```json
  {
    "success": true,
    "synced": [1,2,3],
    "failed": []
  }
  ```

## Exemple d'appel depuis Laravel (PHP)

```php
$signalements = [...]; // Tableau de signalements à synchroniser
$response = Http::post('http://localhost:4000/sync-signalements', [
    'signalements' => $signalements
]);
if ($response->successful()) {
    $result = $response->json();
    // $result['synced'], $result['failed']
}
```

## Sécurité
- Protégez ce service sur un réseau privé ou ajoutez une authentification si besoin.
