import { 
  collection, 
  getDocs, 
  query, 
  orderBy,
  doc,
  getDoc,
  updateDoc,
  where
} from 'firebase/firestore';
import { db } from '@/firebase';
import { Entreprise } from './types';

export class EntrepriseService {
  private collectionName = 'entreprises';
  /**
   * Récupérer une entreprise par ID
   */
  async getById(id: string): Promise<Entreprise | null> {
    try {
      const docRef = doc(db, this.collectionName, id);
      const docSnap = await getDoc(docRef);
      
      if (docSnap.exists()) {
        const data = docSnap.data();
        return {
          id: data.id, // ID numérique du document
          code: data.code,
          nom: data.nom,
          logo: data.logo
        } as Entreprise;
      }
      return null;
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'entreprise:', error);
      throw new Error('Impossible de récupérer l\'entreprise');
    }
  }

  /**
   * Récupérer toutes les entreprises
   */
  async getAll(): Promise<Entreprise[]> {
    try {
      const q = query(
        collection(db, this.collectionName),
        orderBy('nom', 'asc')
      );
      
      const querySnapshot = await getDocs(q);
      
      return querySnapshot.docs.map(doc => {
        const data = doc.data();
        return {
          id: data.id, // ID numérique du document
          code: data.code,
          nom: data.nom,
          logo: data.logo
        } as Entreprise;
      });
    } catch (error) {
      console.error('Erreur lors de la récupération des entreprises:', error);
      throw new Error('Impossible de récupérer les entreprises');
    }
  }

  /**
   * Rechercher des entreprises par nom
   */
  async searchByName(searchTerm: string): Promise<Entreprise[]> {
    try {
      // Recherche simple avec Firestore
      const q = query(
        collection(db, this.collectionName),
        where('nom', '>=', searchTerm),
        where('nom', '<=', searchTerm + '\uf8ff'),
        orderBy('nom')
      );
      
      const querySnapshot = await getDocs(q);
      
      return querySnapshot.docs.map(doc => {
        const data = doc.data();
        return {
          id: data.id, // ID numérique du document
          code: data.code,
          nom: data.nom,
          logo: data.logo
        } as Entreprise;
      });
    } catch (error) {
      console.error('Erreur lors de la recherche d\'entreprises:', error);
      throw new Error('Impossible de rechercher les entreprises');
    }
  }

  /**
   * Mettre à jour une entreprise
   */
  async update(id: string, data: Partial<Omit<Entreprise, 'id'>>): Promise<void> {
    try {
      const docRef = doc(db, this.collectionName, id);
      await updateDoc(docRef, data);
    } catch (error) {
      console.error('Erreur lors de la mise à jour de l\'entreprise:', error);
      throw new Error('Impossible de mettre à jour l\'entreprise');
    }
  }

}