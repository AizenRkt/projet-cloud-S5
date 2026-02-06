const express = require('express');
const admin = require('firebase-admin');
const cors = require('cors');
const fs = require('fs');

// Charger les credentials Firebase depuis un fichier local
const serviceAccount = require('./firebase_credentials.json');

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();
const app = express();
app.use(cors());
app.use(express.json());

// Route de test
app.get('/', (req, res) => {
  res.json({ status: 'ok', message: 'Firestore Sync Service running' });
});

// Route POST pour synchroniser les signalements
app.post('/sync-signalements', async (req, res) => {
  const signalements = req.body.signalements;
  if (!Array.isArray(signalements)) {
    return res.status(400).json({ success: false, message: 'signalements doit être un tableau' });
  }
  const results = { synced: [], failed: [] };
  for (const sig of signalements) {
    try {
      // Mapping des champs pour Firestore
      const firestoreData = {
        budget: sig.budget,
        dateSignalement: sig.date_signalement,
        description: sig.description,
        entrepriseId: String(sig.id_entreprise),
        entrepriseNom: sig.entreprise_nom,
        latitude: sig.latitude,
        longitude: sig.longitude,
        status: sig.statut_libelle,
        surface: sig.surface_m2,
        typeSignalementId: String(sig.id_type_signalement),
        typeSignalementNom: sig.type_signalement,
        utilisateurEmail: sig.utilisateur_email || null,
        utilisateurId: sig.utilisateur_id || null
      };
      let docRef;
      if (sig.firebase_id) {
        docRef = db.collection('signalements').doc(sig.firebase_id);
        await docRef.set(firestoreData, { merge: true });
      } else {
        docRef = await db.collection('signalements').add(firestoreData);
        sig.firebase_id = docRef.id;
      }
      results.synced.push(sig.local_id || sig.firebase_id);
    } catch (e) {
      results.failed.push({ id: sig.local_id, error: e.message });
    }
  }
  res.json({ success: results.failed.length === 0, ...results });
});

const PORT = process.env.PORT || 4000;
app.listen(PORT, () => {
  console.log(`Firestore Sync Service listening on port ${PORT}`);
});
