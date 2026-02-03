import { Timestamp } from 'firebase/firestore';

// Types basés sur le schéma de base de données
export interface TypeSignalement {
  id: number;
  code : string;
  nom: string;
  icon?: string;
}

export interface Entreprise {
  id: number;
  code: string;
  nom: string;
  logo?: string;
}

export interface SignalementData {
  typeSignalementId: number;
  typeSignalementNom?: string;
  entrepriseId?: number;
  entrepriseNom?: string;
  utilisateurId?: string;
  utilisateurEmail?: string;
  latitude: number;
  longitude: number;
  description?: string;
  surface?: number;
  budget?: number;
  dateSignalement?: Date;
}

export interface Signalement extends SignalementData {
  id: string;
  dateSignalement: Date;
}

// Types pour Firebase
export interface FirebaseSignalementData {
  typeSignalementId: string;
  typeSignalementNom?: string;
  entrepriseId?: string | null;
  entrepriseNom?: string | null;
  utilisateurId?: string | null;
  utilisateurEmail?: string | null;
  latitude: number;
  longitude: number;
  description?: string;
  surface?: number | null;
  budget?: number | null;
  dateSignalement: Timestamp;
}