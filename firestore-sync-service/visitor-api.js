const express = require('express');
const admin = require('firebase-admin');
const cors = require('cors');

const serviceAccount = require('./firebase_credentials.json');

if (!admin.apps.length) {
  admin.initializeApp({
    credential: admin.credential.cert(serviceAccount)
  });
}

const db = admin.firestore();
const app = express();

app.use(cors());
app.use(express.json());

function normalizeTimestamp(value) {
  if (value && typeof value.toDate === 'function') {
    return value.toDate().toISOString();
  }
  return value;
}

function normalizeDocumentData(data) {
  const normalized = {};
  Object.keys(data || {}).forEach((key) => {
    normalized[key] = normalizeTimestamp(data[key]);
  });
  return normalized;
}

app.get('/health', (req, res) => {
  res.json({ status: 'ok', message: 'Visitor Firestore API running' });
});

app.get('/public/signalements', async (req, res) => {
  try {
    const snapshot = await db.collection('signalements').get();
    const documents = [];
    snapshot.forEach((doc) => {
      const data = normalizeDocumentData(doc.data());
      documents.push({ firestore_id: doc.id, ...data });
    });
    res.json({ success: true, count: documents.length, data: documents });
  } catch (e) {
    console.error('Erreur lecture signalements publics:', e.message);
    res.status(500).json({ success: false, message: e.message });
  }
});

const PORT = process.env.VISITOR_PORT || 4001;
app.listen(PORT, () => {
  console.log(`Visitor Firestore API listening on port ${PORT}`);
});
