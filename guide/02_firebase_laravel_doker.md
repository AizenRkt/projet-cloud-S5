# 📘 Révision complète : Implémentation Firebase + Laravel + Docker

Ce document sert de **révision détaillée** pour comprendre **comment et pourquoi** on implémente Firebase Authentication avec Laravel, le tout dans un environnement Docker. Il explique **les concepts, les étapes d’installation, la logique d’architecture et les flux register/login**, sans frontend (tests via Postman).

---

## 1️⃣ Objectif du projet

L’objectif est de :

* Utiliser **Firebase Authentication** comme **système d’authentification principal**
* Utiliser **Laravel** comme backend API
* Stocker les données métiers (profil utilisateur, rôles, etc.) dans **PostgreSQL**
* Tester toute l’authentification **uniquement via Postman**
* Isoler l’environnement avec **Docker**

👉 Firebase gère :

* Email / mot de passe
* Tokens JWT (`idToken`, `refreshToken`)

👉 Laravel gère :

* La logique métier
* La base PostgreSQL
* La sécurité API

---

## 2️⃣ Architecture globale

```
Postman
   ↓ (HTTP JSON)
Laravel API (Docker)
   ↓
Firebase Authentication (Cloud)
   ↓
PostgreSQL (Docker)
```

* Firebase **ne remplace pas** ta base de données
* Firebase sert uniquement à **authentifier**
* PostgreSQL stocke les informations métier

---

## 3️⃣ Création du projet Firebase

### 3.1 Créer un projet Firebase

1. Aller sur [https://console.firebase.google.com](https://console.firebase.google.com)
2. Créer un nouveau projet
3. Désactiver Google Analytics (optionnel)

### 3.2 Activer l’authentification Email/Password

1. Firebase Console → Authentication
2. Onglet **Sign-in method**
3. Activer **Email / Password**

⚠️ Sans cette étape, le login ne fonctionnera jamais

---

## 4️⃣ Service Account Firebase (obligatoire)

Laravel communique avec Firebase via une **clé serveur** (service account).

### 4.1 Générer la clé

1. Firebase Console → Project Settings
2. Onglet **Service accounts**
3. Generate new private key
4. Télécharger le fichier JSON

### 4.2 Où placer le fichier

Exemple recommandé :

```
storage/firebase/firebase_credentials.json
```

⚠️ Ce fichier **ne doit jamais être versionné** (Git)

`.gitignore`

```
storage/firebase/*.json
```

---

## 5️⃣ Installation Laravel

### 5.1 Créer le projet

```
composer create-project laravel/laravel backend
```

### 5.2 Installation du SDK Firebase (Kreait)

```
composer require kreait/firebase-php
```

Kreait est le **SDK officiel Firebase pour PHP**.

---

## 6️⃣ Configuration Firebase dans Laravel

### 6.1 Variable d’environnement

Dans `.env` :

```
FIREBASE_CREDENTIALS=/var/www/html/storage/firebase/firebase_credentials.json
```

⚠️ Chemin **absolu dans le conteneur Docker**

### 6.2 Initialisation Firebase

Laravel injecte automatiquement :

```
Kreait\Firebase\Auth
```

Grâce au service provider de Kreait.

---

## 7️⃣ Docker : mise en place

### 7.1 Pourquoi Docker ?

* Même environnement pour tout le monde
* PHP, extensions, PostgreSQL cohérents
* Aucun problème de version

### 7.2 Services Docker typiques

* `app` : Laravel + PHP 8.2
* `db` : PostgreSQL

Laravel tourne **dans un conteneur**, Firebase reste externe (cloud).

---

## 8️⃣ Base de données PostgreSQL

### 8.1 Table users

La table `users` ne sert PAS à l’authentification.

Elle sert à stocker :

* email
* firebase_uid
* nom
* prenom
* rôle

Exemple :

```
id | email | firebase_uid | nom | prenom | id_role
```

Le lien entre Firebase et Laravel = `firebase_uid`

---

## 9️⃣ Logique REGISTER (Inscription)

### 9.1 Étapes logiques

1. Postman envoie email + password + infos
2. Laravel valide les données
3. Laravel crée l’utilisateur **dans Firebase**
4. Firebase retourne un `uid`
5. Laravel stocke le `uid` en base locale

### 9.2 Pourquoi cet ordre ?

* Firebase est la source de vérité pour l’auth
* Si Firebase échoue → on n’écrit rien en base

---

## 🔁 Schéma Register

```
Postman → Laravel → Firebase
                 ← uid
Postman ← Laravel → PostgreSQL
```

---

## 1️⃣0️⃣ Logique LOGIN

### 10.1 Étapes logiques

1. Postman envoie email + password
2. Laravel appelle Firebase
3. Firebase vérifie les identifiants
4. Firebase retourne :

   * idToken
   * refreshToken

Laravel **ne génère pas de token**.

Firebase est l’autorité.

---

## 1️⃣1️⃣ Le idToken (JWT)

* Durée courte (~1h)
* Signé par Firebase
* Contient le `uid`

Utilisé dans :

```
Authorization: Bearer <idToken>
```

---

## 1️⃣2️⃣ Middleware firebase.auth

### Rôle

* Vérifier que le token est valide
* Extraire le `uid`
* Bloquer les requêtes non authentifiées

Sans token valide → 401

---

## 1️⃣3️⃣ Update du profil

1. Postman envoie token + données
2. Laravel vérifie le token Firebase
3. Laravel récupère le `uid`
4. Mise à jour Firebase (email si besoin)
5. Mise à jour PostgreSQL (nom, prénom)

Firebase = auth
PostgreSQL = données métier

---

## 1️⃣4️⃣ Erreurs fréquentes (à retenir)

### ❌ invalid_grant

* Email inexistant dans Firebase
* Mot de passe incorrect
* Heure serveur incorrecte
* Service account invalide

### ❌ 504 Gateway Timeout

* Firebase inaccessible
* Mauvais DNS
* Mauvaise config Docker

---

## 1️⃣5️⃣ Bonnes pratiques

✅ Toujours tester register AVANT login
✅ Toujours vérifier Firebase Console
✅ Ne jamais stocker le mot de passe localement
✅ Ne jamais versionner la clé Firebase

---

## 1️⃣6️⃣ Résumé final

* Firebase = authentification
* Laravel = API + logique métier
* PostgreSQL = stockage
* Docker = environnement
* Postman = tests

👉 Cette architecture est **propre, scalable et sécurisée**.

---

📌 **Fin du document de révision**
