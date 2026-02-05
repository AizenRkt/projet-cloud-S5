// Gestion des tentatives de connexion et blocage utilisateur
import { loginConfig } from './loginConfig';
import { db } from '@/firebase';
import { collection, addDoc, setDoc, doc, deleteDoc, getDocs, query, where } from 'firebase/firestore';

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
  const attempt = { email, timestamp: Date.now(), success };
  attempts.push(attempt);
  localStorage.setItem(ATTEMPTS_KEY, JSON.stringify(attempts));
  // Envoi Firestore
  try {
    await addDoc(collection(db, 'tentatives_connexion'), attempt);
  } catch (e) {
    console.error('Erreur Firestore tentative_connexion:', e);
  }
}

// Vérifier si l'utilisateur est bloqué
export function isBlocked(email: string): boolean {
  const blocked = JSON.parse(localStorage.getItem(BLOCKED_KEY) || '{}');
  if (!blocked[email]) return false;
  // Débloquer si le temps est écoulé
  const now = Date.now();
  if (now - blocked[email] > loginConfig.loginAttemptsMinutes * 60 * 1000) {
    unblock(email);
    return false;
  }
  return true;
}

// Bloquer l'utilisateur
export async function block(email: string) {
  const blocked = JSON.parse(localStorage.getItem(BLOCKED_KEY) || '{}');
  blocked[email] = Date.now();
  localStorage.setItem(BLOCKED_KEY, JSON.stringify(blocked));
  // Envoi Firestore
  try {
    await setDoc(doc(db, 'utilisateurs_bloques', email), {
      email,
      date_blocage: Date.now()
    });
  } catch (e) {
    console.error('Erreur Firestore block:', e);
  }
}


// Débloquer l'utilisateur et supprimer ses tentatives
export async function unblock(email: string) {
  const blocked = JSON.parse(localStorage.getItem(BLOCKED_KEY) || '{}');
  delete blocked[email];
  localStorage.setItem(BLOCKED_KEY, JSON.stringify(blocked));

  // Supprimer les tentatives locales
  const attempts = getAttempts().filter(a => a.email !== email);
  localStorage.setItem(ATTEMPTS_KEY, JSON.stringify(attempts));

  // Supprimer Firestore
  try {
    await deleteDoc(doc(db, 'utilisateurs_bloques', email));
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


// Vérifier le nombre de tentatives
export async function checkAttempts(email: string): Promise<boolean> {
  const attempts = getAttempts().filter(a => a.email === email && !a.success);
  if (attempts.length >= loginConfig.loginAttemptsLimit) {
    await block(email);
    return false;
  }
  return true;
}

// Appeler lors d'une tentative de connexion
export async function handleLoginAttempt(email: string, success: boolean) {
  await addAttempt(email, success);
  if (!success) {
    await checkAttempts(email);
  } else {
    await unblock(email);
  }
}
