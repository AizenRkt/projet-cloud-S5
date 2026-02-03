import { initializeApp } from 'firebase/app';
import { getFirestore, collection, doc, setDoc } from 'firebase/firestore';

// Configuration Firebase (même que dans firebase.ts)
const firebaseConfig = {
  apiKey: "AIzaSyC7XeriIaw-HtnK6NYM7bAgaut6rjltTqo",
  authDomain: "road-check-a6a4a.firebaseapp.com",
  projectId: "road-check-a6a4a",
  storageBucket: "road-check-a6a4a.firebasestorage.app",
  messagingSenderId: "898288281421",
  appId: "1:898288281421:web:68e264ba05d0b319da7904",
  measurementId: "G-Y0860E5607"
};

// Initialiser Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// Données d'initialisation
const typesSignalement = [
  { id: 1 , code: 'nid-de-poule', nom: 'Nid de poule', icon: '🕳️' },
  { id: 2, code: 'fissure', nom: 'Fissure', icon: '💥' },
  { id: 3, code: 'chaussee-affaissee', nom: 'Chaussée affaissée', icon: '⬇️' },
  { id: 4, code: 'marquage-efface', nom: 'Marquage effacé', icon: '🎨' },
  { id: 5, code: 'panneau-endommage', nom: 'Panneau endommagé', icon: '⚠️' },
  { id: 6, code: 'eclairage-defaillant', nom: 'Éclairage défaillant', icon: '💡' },
  { id: 7, code: 'evacuation-bouchee', nom: 'Évacuation bouchée', icon: '🌊' },
  { id: 8, code: 'vegetation-envahissante', nom: 'Végétation envahissante', icon: '🌿' }
];

const entreprises = [
  { id: 1, code: 'voirie-mada', nom: 'Voirie Madagascar', logo: '' },
  { id: 2, code: 'tp-antananarivo', nom: 'TP Antananarivo', logo: '' },
  { id: 3, code: 'sobatram', nom: 'SOBATRAM', logo: '' },
  { id: 4, code: 'colas-madagascar', nom: 'Colas Madagascar', logo: '' },
  { id: 5, code: 'sogea-satom', nom: 'Sogea-Satom Madagascar', logo: '' }
];

async function initializeFirestore() {
  try {
    console.log('Initialisation de Firestore...');

    // 1. Créer les types de signalement
    console.log('Création des types de signalement...');
    for (const type of typesSignalement) {
      await setDoc(doc(db, 'types_signalement', type.id.toString()), {
        id: type.id,
        code: type.code,
        nom: type.nom,
        icon: type.icon
      });
      console.log(`Type créé: ${type.nom} (ID: ${type.id})`);
    }

    // 2. Créer les entreprises
    console.log('Création des entreprises...');
    for (const entreprise of entreprises) {
      await setDoc(doc(db, 'entreprises', entreprise.id.toString()), {
        id: entreprise.id,
        code: entreprise.code,
        nom: entreprise.nom,
        logo: entreprise.logo
      });
      console.log(`Entreprise créée: ${entreprise.nom} (ID: ${entreprise.id})`);
    }

    console.log('Initialisation terminée avec succès !');
    console.log(`${typesSignalement.length} types de signalement créés`);
    console.log(`${entreprises.length} entreprises créées`);

  } catch (error) {
    console.error('Erreur lors de l\'initialisation:', error);
  }
}

// Exécuter l'initialisation
initializeFirestore();