import { auth } from "@/firebase";
import { signInWithEmailAndPassword, createUserWithEmailAndPassword, signOut } from "firebase/auth";

// Connexion
export const login = (email: string, password: string) =>
  signInWithEmailAndPassword(auth, email, password);

// Inscription
export const register = (email: string, password: string) =>
  createUserWithEmailAndPassword(auth, email, password);

// Déconnexion
export const logout = () => {
  return signOut(auth);
};