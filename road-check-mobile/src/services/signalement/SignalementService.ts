import { 
  collection, 
  addDoc, 
  getDocs, 
  query, 
  where, 
  orderBy, 
  Timestamp,
  doc,
  getDoc,
  updateDoc,
  deleteDoc
} from 'firebase/firestore';
import { db } from '@/firebase';
import { 
  SignalementData, 
  Signalement, 
  FirebaseSignalementData,
  SignalementStatus
} from './types';

export class SignalementService {
  private collectionName = 'signalements';

  /**
   * Créer un nouveau signalement
   */
  async create(data: SignalementData): Promise<string> {
    try {
      const signalementData: FirebaseSignalementData = {
        typeSignalementId: data.typeSignalementId?.toString() || '',
        typeSignalementNom: data.typeSignalementNom,
        entrepriseId: data.entrepriseId ? data.entrepriseId.toString() : null,
        entrepriseNom: data.entrepriseNom,
        utilisateurId: data.utilisateurId || null,
        utilisateurEmail: data.utilisateurEmail || null,
        latitude: data.latitude,
        longitude: data.longitude,
        description: data.description || '',
        surface: data.surface || null,
        budget: data.budget || null,
        dateSignalement: Timestamp.fromDate(data.dateSignalement || new Date()),
        status: SignalementStatus.EN_ATTENTE // Statut par défaut lors de la création
      };

      const docRef = await addDoc(collection(db, this.collectionName), signalementData);
      console.log('Signalement créé avec ID:', docRef.id);
      return docRef.id;
    } catch (error) {
      console.error('Erreur lors de la création du signalement:', error);
      throw new Error('Impossible de créer le signalement');
    }
  }

  /**
   * Récupérer un signalement par ID
   */
  async getById(id: string): Promise<Signalement | null> {
    try {
      const docRef = doc(db, this.collectionName, id);
      const docSnap = await getDoc(docRef);
      
      if (docSnap.exists()) {
        const data = docSnap.data();
        return {
          id: docSnap.id,
          ...data,
          dateSignalement: data.dateSignalement.toDate(),
          status: data.status || SignalementStatus.EN_ATTENTE
        } as Signalement;
      }
      return null;
    } catch (error) {
      console.error('Erreur lors de la récupération du signalement:', error);
      throw new Error('Impossible de récupérer le signalement');
    }
  }

  /**
   * Récupérer tous les signalements
   */
  async getAll(): Promise<Signalement[]> {
    try {
      const q = query(
        collection(db, this.collectionName),
        orderBy('dateSignalement', 'desc')
      );
      
      const querySnapshot = await getDocs(q);
      
      return querySnapshot.docs.map(doc => {
        const data = doc.data();
        return {
          id: doc.id,
          typeSignalementId: parseInt(data.typeSignalementId) || 0,
          typeSignalementNom: data.typeSignalementNom,
          entrepriseId: data.entrepriseId ? parseInt(data.entrepriseId) : undefined,
          entrepriseNom: data.entrepriseNom,
          utilisateurId: data.utilisateurId,
          utilisateurEmail: data.utilisateurEmail,
          latitude: data.latitude,
          longitude: data.longitude,
          description: data.description,
          surface: data.surface,
          budget: data.budget,
          dateSignalement: data.dateSignalement.toDate(),
          status: data.status || SignalementStatus.EN_ATTENTE
        } as Signalement;
      });
    } catch (error) {
      console.error('Erreur lors de la récupération des signalements:', error);
      throw new Error('Impossible de récupérer les signalements');
    }
  }

  /**
   * Récupérer les signalements par zone géographique
   */
  async getByZone(
    latMin: number, 
    latMax: number, 
    lngMin: number, 
    lngMax: number
  ): Promise<Signalement[]> {
    try {
      // Firestore limitation: on peut seulement faire une requête range sur un champ
      const q = query(
        collection(db, this.collectionName),
        where('latitude', '>=', latMin),
        where('latitude', '<=', latMax),
        orderBy('latitude')
      );
      
      const querySnapshot = await getDocs(q);
      
      // Filtrer côté client pour la longitude
      return querySnapshot.docs
        .map(doc => ({
          id: doc.id,
          ...doc.data(),
          dateSignalement: doc.data().dateSignalement.toDate()
        } as Signalement))
        .filter(signalement => 
          signalement.longitude >= lngMin && 
          signalement.longitude <= lngMax
        );
    } catch (error) {
      console.error('Erreur lors de la récupération par zone:', error);
      throw new Error('Impossible de récupérer les signalements par zone');
    }
  }

  /**
   * Récupérer les signalements par utilisateur
   */
  async getByUser(utilisateurId: string): Promise<Signalement[]> {
    try {
      const q = query(
        collection(db, this.collectionName),
        where('utilisateurId', '==', utilisateurId),
        orderBy('dateSignalement', 'desc')
      );
      
      const querySnapshot = await getDocs(q);
      
      return querySnapshot.docs.map(doc => {
        const data = doc.data();
        return {
          id: doc.id,
          ...data,
          dateSignalement: data.dateSignalement.toDate(),
          status: data.status || SignalementStatus.EN_ATTENTE
        } as Signalement;
      });
    } catch (error) {
      console.error('Erreur lors de la récupération par utilisateur:', error);
      throw new Error('Impossible de récupérer les signalements de l\'utilisateur');
    }
  }

  /**
   * Mettre à jour un signalement
   */
  async update(id: string, data: Partial<SignalementData>): Promise<void> {
    try {
      const docRef = doc(db, this.collectionName, id);
      const updateData: Partial<FirebaseSignalementData> = {};

      // Convertir les données si nécessaire
      if (data.dateSignalement) {
        updateData.dateSignalement = Timestamp.fromDate(data.dateSignalement);
      }

      // Copier les autres champs
      Object.keys(data).forEach(key => {
        if (key !== 'dateSignalement') {
          (updateData as any)[key] = (data as any)[key];
        }
      });

      await updateDoc(docRef, updateData);
    } catch (error) {
      console.error('Erreur lors de la mise à jour:', error);
      throw new Error('Impossible de mettre à jour le signalement');
    }
  }

  /**
   * Supprimer un signalement
   */
  async delete(id: string): Promise<void> {
    try {
      const docRef = doc(db, this.collectionName, id);
      await deleteDoc(docRef);
    } catch (error) {
      console.error('Erreur lors de la suppression:', error);
      throw new Error('Impossible de supprimer le signalement');
    }
  }

}