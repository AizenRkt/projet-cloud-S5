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

// Helper pour générer un code/slug à partir d'un nom
function slugify(text) {
  return text.toString().toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

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
      // Mapping des champs pour Firestore (collection signalements)
      const firestoreData = {
        budget: sig.budget || 0,
        dateSignalement: sig.date_signalement ? new Date(sig.date_signalement) : new Date(),
        dateStatus: sig.date_status ? new Date(sig.date_status) : new Date(),
        description: sig.description || '',
        entrepriseId: sig.id_entreprise ? String(sig.id_entreprise) : null,
        entrepriseNom: sig.entreprise_nom || null,
        latitude: sig.latitude,
        longitude: sig.longitude,
        photos: Array.isArray(sig.photos) ? sig.photos : [],
        status: sig.statut || 'nouveau',
        surface: sig.surface_m2 || 0,
        typeSignalementId: sig.id_type_signalement ? String(sig.id_type_signalement) : null,
        typeSignalementNom: sig.type_signalement || null,
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

// Route POST pour synchroniser TOUTES les collections PG → Firestore (asynchrone, réponse immédiate)
app.post('/sync-all-to-firestore', (req, res) => {
  // Répondre immédiatement pour éviter le timeout HTTP
  res.json({ success: true, message: 'Synchronisation Firestore lancée en arrière-plan.' });

  // Lancer la synchronisation en tâche de fond
  (async () => {
    const { entreprises, types_signalement, utilisateurs, signalements, tentatives_connexion } = req.body;
    const results = {
      entreprises: { synced: 0, failed: 0, duplicates_removed: 0 },
      types_signalement: { synced: 0, failed: 0, duplicates_removed: 0 },
      utilisateurs: { synced: 0, failed: 0, duplicates_removed: 0 },
      signalements: { synced: 0, failed: 0, duplicates_removed: 0 },
      tentatives_connexion: { synced: 0, failed: 0, duplicates_removed: 0 },
      synced_ids: []
    };
    try {
      // ...existing code for synchronisation (copié tel quel de la version précédente)...
      // 1. Entreprises
      if (Array.isArray(entreprises)) {
        for (const ent of entreprises) {
          try {
            const data = {
              id: ent.id_entreprise,
              code: slugify(ent.nom || ''),
              nom: ent.nom || '',
              logo: ent.logo || null
            };
            const existing = await db.collection('entreprises').where('nom', '==', ent.nom).get();
            if (!existing.empty) {
              await existing.docs[0].ref.set(data, { merge: true });
              for (let i = 1; i < existing.docs.length; i++) {
                await existing.docs[i].ref.delete();
                results.entreprises.duplicates_removed++;
              }
            } else {
              const existingById = await db.collection('entreprises').where('id', '==', ent.id_entreprise).get();
              if (!existingById.empty) {
                await existingById.docs[0].ref.set(data, { merge: true });
                for (let i = 1; i < existingById.docs.length; i++) {
                  await existingById.docs[i].ref.delete();
                  results.entreprises.duplicates_removed++;
                }
              } else {
                await db.collection('entreprises').doc(`ent_${ent.id_entreprise}`).set(data);
              }
            }
            results.entreprises.synced++;
          } catch (e) { results.entreprises.failed++; console.error('Erreur entreprise:', e.message); }
        }
      }
      // 2. Types signalement
      if (Array.isArray(types_signalement)) {
        for (const ts of types_signalement) {
          try {
            const data = {
              id: ts.id_type_signalement,
              code: slugify(ts.nom || ''),
              nom: ts.nom || '',
              icon: ts.icon || null
            };
            const existing = await db.collection('types_signalement').where('nom', '==', ts.nom).get();
            if (!existing.empty) {
              await existing.docs[0].ref.set(data, { merge: true });
              for (let i = 1; i < existing.docs.length; i++) {
                await existing.docs[i].ref.delete();
                results.types_signalement.duplicates_removed++;
              }
            } else {
              const existingById = await db.collection('types_signalement').where('id', '==', ts.id_type_signalement).get();
              if (!existingById.empty) {
                await existingById.docs[0].ref.set(data, { merge: true });
                for (let i = 1; i < existingById.docs.length; i++) {
                  await existingById.docs[i].ref.delete();
                  results.types_signalement.duplicates_removed++;
                }
              } else {
                await db.collection('types_signalement').doc(`ts_${ts.id_type_signalement}`).set(data);
              }
            }
            results.types_signalement.synced++;
          } catch (e) { results.types_signalement.failed++; console.error('Erreur type_signalement:', e.message); }
        }
      }
      // 3. Utilisateurs
      if (Array.isArray(utilisateurs)) {
        for (const u of utilisateurs) {
          try {
            const data = {
              email: u.email || '',
              firebase_uid: u.firebase_uid || null,
              id_role: u.id_role || 3,
              role: u.role || 'Utilisateur',
              bloque: u.bloque === true,
              date_creation: u.date_creation ? new Date(u.date_creation) : new Date()
            };
            if (u.nom) data.nom = u.nom;
            if (u.prenom) data.prenom = u.prenom;
            const existing = await db.collection('utilisateurs').where('email', '==', u.email).get();
            if (!existing.empty) {
              const existingData = existing.docs[0].data();
              data.bloque = (data.bloque === true) || (existingData.bloque === true);
              if (!data.nom && existingData.nom) data.nom = existingData.nom;
              if (!data.prenom && existingData.prenom) data.prenom = existingData.prenom;
              await existing.docs[0].ref.set(data, { merge: true });
              for (let i = 1; i < existing.docs.length; i++) {
                await existing.docs[i].ref.delete();
                results.utilisateurs.duplicates_removed++;
              }
            } else {
              if (!data.nom) data.nom = '';
              if (!data.prenom) data.prenom = '';
              await db.collection('utilisateurs').doc(`user_${u.id_utilisateur}`).set(data);
            }
            results.utilisateurs.synced++;
          } catch (e) { results.utilisateurs.failed++; console.error('Erreur utilisateur:', e.message); }
        }
      }
      // 4. Signalements
      if (Array.isArray(signalements)) {
        for (const sig of signalements) {
          try {
            const pgPhotos = Array.isArray(sig.photos) ? sig.photos : [];
            const firestoreData = {
              budget: sig.budget || 0,
              dateSignalement: sig.date_signalement ? new Date(sig.date_signalement) : new Date(),
              dateStatus: sig.date_status ? new Date(sig.date_status) : new Date(),
              description: sig.description || '',
              entrepriseId: sig.id_entreprise ? String(sig.id_entreprise) : null,
              entrepriseNom: sig.entreprise_nom || null,
              latitude: sig.latitude,
              longitude: sig.longitude,
              status: sig.statut || 'nouveau',
              surface: sig.surface_m2 || 0,
              typeSignalementId: sig.id_type_signalement ? String(sig.id_type_signalement) : null,
              typeSignalementNom: sig.type_signalement || null
            };
            if (sig.firebase_id) {
              const existingDoc = await db.collection('signalements').doc(sig.firebase_id).get();
              let mergedPhotos = [...pgPhotos];
              if (existingDoc.exists) {
                const existData = existingDoc.data();
                const existingPhotos = existData.photos || [];
                for (const p of existingPhotos) {
                  if (p && !mergedPhotos.includes(p)) mergedPhotos.push(p);
                }
                if (existData.utilisateurEmail || existData.utilisateurId) {
                  firestoreData.utilisateurEmail = existData.utilisateurEmail;
                  firestoreData.utilisateurId = existData.utilisateurId;
                } else {
                  firestoreData.utilisateurEmail = sig.utilisateur_email || null;
                  firestoreData.utilisateurId = sig.utilisateur_id || null;
                }
              } else {
                firestoreData.utilisateurEmail = sig.utilisateur_email || null;
                firestoreData.utilisateurId = sig.utilisateur_id || null;
              }
              firestoreData.photos = mergedPhotos;
              await db.collection('signalements').doc(sig.firebase_id).set(firestoreData, { merge: true });
            } else {
              const existing = await db.collection('signalements')
                .where('latitude', '==', sig.latitude)
                .where('longitude', '==', sig.longitude)
                .get();
              const matches = existing.docs.filter(d => (d.data().description || '') === (sig.description || ''));
              if (matches.length > 0) {
                const matchData = matches[0].data();
                const existingPhotos = matchData.photos || [];
                let mergedPhotos = [...pgPhotos];
                for (const p of existingPhotos) {
                  if (p && !mergedPhotos.includes(p)) mergedPhotos.push(p);
                }
                firestoreData.photos = mergedPhotos;
                if (matchData.utilisateurEmail || matchData.utilisateurId) {
                  firestoreData.utilisateurEmail = matchData.utilisateurEmail;
                  firestoreData.utilisateurId = matchData.utilisateurId;
                } else {
                  firestoreData.utilisateurEmail = sig.utilisateur_email || null;
                  firestoreData.utilisateurId = sig.utilisateur_id || null;
                }
                await matches[0].ref.set(firestoreData, { merge: true });
                sig.firebase_id = matches[0].id;
                for (let i = 1; i < matches.length; i++) {
                  await matches[i].ref.delete();
                  results.signalements.duplicates_removed++;
                }
              } else {
                firestoreData.utilisateurEmail = sig.utilisateur_email || null;
                firestoreData.utilisateurId = sig.utilisateur_id || null;
                firestoreData.photos = pgPhotos;
                const docRef = await db.collection('signalements').add(firestoreData);
                sig.firebase_id = docRef.id;
              }
            }
            results.signalements.synced++;
            results.synced_ids.push(sig.local_id || sig.firebase_id);
          } catch (e) { results.signalements.failed++; console.error('Erreur signalement:', e.message); }
        }
      }
      // 5. Tentatives connexion
      if (Array.isArray(tentatives_connexion)) {
        for (const tc of tentatives_connexion) {
          try {
            const timestamp = tc.date_tentative ? new Date(tc.date_tentative).getTime() : Date.now();
            const data = {
              email: tc.utilisateur_email || null,
              success: tc.succes || false,
              timestamp: timestamp
            };
            const existing = await db.collection('tentatives_connexion')
              .where('email', '==', data.email)
              .where('timestamp', '==', timestamp)
              .get();
            if (!existing.empty) {
              await existing.docs[0].ref.set(data, { merge: true });
              for (let i = 1; i < existing.docs.length; i++) {
                await existing.docs[i].ref.delete();
                results.tentatives_connexion.duplicates_removed++;
              }
            } else {
              await db.collection('tentatives_connexion').doc(`tc_${tc.id_tentative}`).set(data);
            }
            results.tentatives_connexion.synced++;
          } catch (e) { results.tentatives_connexion.failed++; console.error('Erreur tentative_connexion:', e.message); }
        }
      }
      // Résumé log
      console.log('[SYNC] Synchronisation Firestore terminée', results);
    } catch (e) {
      console.error('Erreur sync-all-to-firestore:', e.message);
    }
  })();
});

// ==================== REVERSE SYNC: Firestore → PostgreSQL ====================

// Route POST pour mettre à jour le champ bloque d'un utilisateur par email
app.post('/update-user-bloque', async (req, res) => {
  const { email, bloque } = req.body;
  if (!email) {
    return res.status(400).json({ success: false, message: 'email requis' });
  }
  try {
    const snapshot = await db.collection('utilisateurs').where('email', '==', email).get();
    if (snapshot.empty) {
      return res.json({ success: true, message: 'Utilisateur non trouvé dans Firestore, rien à mettre à jour' });
    }
    for (const doc of snapshot.docs) {
      await doc.ref.update({ bloque: bloque === true || bloque === 'true' ? true : false });
    }
    res.json({ success: true, message: `Utilisateur ${email} mis à jour: bloque=${bloque}` });
  } catch (e) {
    console.error('Erreur update-user-bloque:', e.message);
    res.status(500).json({ success: false, message: e.message });
  }
});

// Route GET pour lire une collection Firestore
app.get('/get-collection/:name', async (req, res) => {
  const collectionName = req.params.name;
  const allowed = ['entreprises', 'signalements', 'tentatives_connexion', 'types_signalement', 'utilisateurs'];
  if (!allowed.includes(collectionName)) {
    return res.status(400).json({ success: false, message: `Collection "${collectionName}" non autorisée` });
  }
  try {
    const snapshot = await db.collection(collectionName).get();
    const documents = [];
    snapshot.forEach(doc => {
      documents.push({ firestore_id: doc.id, ...doc.data() });
    });
    res.json({ success: true, collection: collectionName, count: documents.length, data: documents });
  } catch (e) {
    console.error(`Erreur lecture collection ${collectionName}:`, e.message);
    res.status(500).json({ success: false, message: e.message });
  }
});

// Route GET pour lire toutes les collections d'un coup (reverse sync complet)
app.get('/get-all-collections', async (req, res) => {
  const collections = ['entreprises', 'signalements', 'tentatives_connexion', 'types_signalement', 'utilisateurs'];
  const result = {};
  try {
    for (const name of collections) {
      const snapshot = await db.collection(name).get();
      const documents = [];
      snapshot.forEach(doc => {
        // Convertir les Timestamps Firestore en ISO strings
        const data = doc.data();
        for (const key in data) {
          if (data[key] && typeof data[key].toDate === 'function') {
            data[key] = data[key].toDate().toISOString();
          }
        }
        documents.push({ firestore_id: doc.id, ...data });
      });
      result[name] = documents;
    }
    res.json({ success: true, data: result });
  } catch (e) {
    console.error('Erreur lecture collections:', e.message);
    res.status(500).json({ success: false, message: e.message });
  }
});

const PORT = process.env.PORT || 4000;
app.listen(PORT, () => {
  console.log(`Firestore Sync Service listening on port ${PORT}`);
});
