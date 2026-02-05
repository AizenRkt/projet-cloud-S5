import { Timestamp } from 'firebase/firestore';

// Enum pour le statut des signalements
export enum SignalementStatus {
  EN_ATTENTE = 'en_attente',
  NOUVEAU = 'nouveau',
  EN_COURS = 'en_cours',
  TERMINE = 'termine',
  ANNULE = 'annule'
}

// Labels et couleurs pour l'affichage des statuts
export const SignalementStatusConfig: Record<SignalementStatus, { label: string; color: string; icon: string }> = {
  [SignalementStatus.EN_ATTENTE]: {
    label: 'En attente',
    color: '#F59E0B',
    icon: 'hourglass-outline'
  },
  [SignalementStatus.NOUVEAU]: {
    label: 'Validé',
    color: '#3B82F6',
    icon: 'checkmark-circle-outline'
  },
  [SignalementStatus.EN_COURS]: {
    label: 'En cours',
    color: '#8B5CF6',
    icon: 'construct-outline'
  },
  [SignalementStatus.TERMINE]: {
    label: 'Terminé',
    color: '#10B981',
    icon: 'checkmark-done-outline'
  },
  [SignalementStatus.ANNULE]: {
    label: 'Annulé',
    color: '#EF4444',
    icon: 'close-circle-outline'
  }
};

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
  status?: SignalementStatus;
}

export interface Signalement extends SignalementData {
  id: string;
  dateSignalement: Date;
  status: SignalementStatus;
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
  status?: string;
}