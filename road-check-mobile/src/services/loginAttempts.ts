// Gestion des tentatives de connexion et blocage utilisateur
import { loginConfig } from './loginConfig';
import { db } from '@/firebase';
import { collection, addDoc, updateDoc, setDoc, doc, deleteDoc, getDocs, query, where } from 'firebase/firestore';

interface LoginAttempt {
  email: string;
  timestamp: number;
  success: boolean;
}

const ATTEMPTS_KEY = 'login_attempts';
const BLOCKED_KEY = 'blocked_users';

// Récupérer les tentatives depuis le localStorage
function getAttempts(): LoginAttempt[] {
  return JSON.parse(localStorage.getItem(ATTEMPTS_KEY) || '[]');
}

// Enregistrer une tentative
async function addAttempt(email: string, success: boolean) {
  const attempts = getAttempts();
  const attempt: LoginAttempt = { email, timestamp: Date.now(), success };
  attempts.push(attempt);
  localStorage.setItem(ATTEMPTS_KEY, JSON.stringify(attempts));
  // Envoi Firestore
  try {
    await addDoc(collection(db, 'tentatives_connexion'), attempt);
  } catch (e) {
    console.error('Erreur Firestore tentative_connexion:', e);
  }
}

// Trouver le document utilisateur par email dans la collection utilisateurs
async function findUtilisateurDocByEmail(email: string) {
  try {
    const q = query(collection(db, 'utilisateurs'), where('email', '==', email));
    const snap = await getDocs(q);
    if (!snap.empty) {
      return snap.docs[0];
    }
  } catch (e) {
    console.error('Erreur Firestore recherche utilisateur:', e);
  }
  return null;
}

// Compter les tentatives échouées récentes depuis Firestore + localStorage
async function countRecentFailedAttempts(email: string): Promise<number> {
  const windowMs = loginConfig.loginAttemptsMinutes * 60 * 1000;
  const cutoff = Date.now() - windowMs;

  // 1. Compteur local (tentatives récentes échouées dans la fenêtre de temps)
  const localAttempts = getAttempts().filter(
    a => a.email === email && !a.success && a.timestamp >= cutoff
  );

  // 2. Compteur Firestore (tentatives récentes échouées dans la fenêtre de temps)
  let firestoreCount = 0;
  try {
    const q = query(
      collection(db, 'tentatives_connexion'),
      where('email', '==', email),
      where('success', '==', false)
    );
    const snap = await getDocs(q);
    snap.docs.forEach(d => {
      const ts = d.data().timestamp;
      if (typeof ts === 'number' && ts >= cutoff) {
        firestoreCount++;
      }
    });
  } catch (e) {
    console.error('Erreur comptage tentatives Firestore:', e);
  }

  // Prendre le maximum des deux sources pour ne pas rater des tentatives
  return Math.max(localAttempts.length, firestoreCount);
}

// Vérifier si l'utilisateur est bloqué (local + Firestore)
export async function isBlocked(email: string): Promise<boolean> {
  // Vérification locale
  const blocked = JSON.parse(localStorage.getItem(BLOCKED_KEY) || '{}');
  if (blocked[email]) {
    const now = Date.now();
    // Si la durée de blocage est passée, on vérifie si le blocage tient toujours
    if (now - blocked[email] > loginConfig.loginAttemptsMinutes * 60 * 1000) {
      // Vérifier si PG/Firestore a toujours bloque=true (le manager n'a pas débloqué)
      const userDoc = await findUtilisateurDocByEmail(email);
      if (userDoc && userDoc.data().bloque === true) {
        // Le manager n'a pas encore débloqué dans PG → rester bloqué
        return true;
      }
      // Le temps est écoulé et PG/Firestore dit bloque=false → débloquer
      await unblock(email);
      return false;
    }
    return true;
  }

  // Vérification Firestore: champ bloque sur le document utilisateur
  try {
    const userDoc = await findUtilisateurDocByEmail(email);
    if (userDoc && userDoc.data().bloque === true) {
      // Synchroniser le blocage en local aussi
      blocked[email] = Date.now();
      localStorage.setItem(BLOCKED_KEY, JSON.stringify(blocked));
      return true;
    }
  } catch (e) {
    console.error('Erreur vérification blocage Firestore:', e);
  }

  // Vérification par comptage des tentatives échouées récentes
  const failedCount = await countRecentFailedAttempts(email);
  if (failedCount >= loginConfig.loginAttemptsLimit) {
    await block(email);
    return true;
  }

  return false;
}

// Bloquer l'utilisateur : mettre bloque = true dans la collection utilisateurs Firestore
export async function block(email: string) {
  const blocked = JSON.parse(localStorage.getItem(BLOCKED_KEY) || '{}');
  blocked[email] = Date.now();
  localStorage.setItem(BLOCKED_KEY, JSON.stringify(blocked));

  // Mettre à jour bloque = true dans Firestore collection utilisateurs
  try {
    const userDoc = await findUtilisateurDocByEmail(email);
    if (userDoc) {
      await updateDoc(userDoc.ref, { bloque: true });
      console.log(`✅ Firestore: bloque=true pour ${email}`);
    } else {
      // L'utilisateur n'existe pas encore → créer un document
      await addDoc(collection(db, 'utilisateurs'), {
        email: email,
        password: null,
        firebase_uid: null,
        nom: '',
        prenom: '',
        id_role: 3,
        role: 'Utilisateur',
        bloque: true,
        date_creation: new Date()
      });
      console.log(`✅ Firestore: utilisateur créé avec bloque=true pour ${email}`);
    }
  } catch (e) {
    console.error('Erreur Firestore block utilisateur:', e);
  }
}

// Débloquer l'utilisateur : mettre bloque = false et supprimer ses tentatives
export async function unblock(email: string) {
  const blocked = JSON.parse(localStorage.getItem(BLOCKED_KEY) || '{}');
  delete blocked[email];
  localStorage.setItem(BLOCKED_KEY, JSON.stringify(blocked));

  // Supprimer les tentatives locales pour cet email
  const attempts = getAttempts().filter(a => a.email !== email);
  localStorage.setItem(ATTEMPTS_KEY, JSON.stringify(attempts));

  // Mettre à jour bloque = false dans Firestore
  try {
    const userDoc = await findUtilisateurDocByEmail(email);
    if (userDoc) {
      await updateDoc(userDoc.ref, { bloque: false });
      console.log(`✅ Firestore: bloque=false pour ${email}`);
    }
    // Supprimer toutes les tentatives Firestore pour cet email
    const q = query(collection(db, 'tentatives_connexion'), where('email', '==', email));
    const snap = await getDocs(q);
    for (const docu of snap.docs) {
      await deleteDoc(docu.ref);
    }
  } catch (e) {
    console.error('Erreur Firestore unblock:', e);
  }
}

// Vérifier le nombre de tentatives échouées récentes
export async function checkAttempts(email: string): Promise<boolean> {
  const failedCount = await countRecentFailedAttempts(email);
  console.log(`📊 Tentatives échouées récentes pour ${email}: ${failedCount}/${loginConfig.loginAttemptsLimit}`);
  if (failedCount >= loginConfig.loginAttemptsLimit) {
    await block(email);
    return false; // bloqué
  }
  return true; // pas encore bloqué
}

// Appeler lors d'une tentative de connexion
export async function handleLoginAttempt(email: string, success: boolean) {
  await addAttempt(email, success);
  if (!success) {
    await checkAttempts(email);
  } else {
    // Connexion réussie → nettoyer les tentatives locales (pas Firestore pour la sync)
    const attempts = getAttempts().filter(a => a.email !== email || a.success);
    localStorage.setItem(ATTEMPTS_KEY, JSON.stringify(attempts));
    // Ne PAS débloquer automatiquement: seul le manager peut débloquer via PG
    // Si l'utilisateur réussit à se connecter, il n'est pas bloqué
  }
}
