import { 
  collection, 
  addDoc, 
  getDocs, 
  query, 
  orderBy,
  doc,
  getDoc,
  updateDoc,
  deleteDoc,
  where
} from 'firebase/firestore';
import { db } from '@/firebase';
import { TypeSignalement } from './types';

export class TypeSignalementService {
  private collectionName = 'types_signalement';

  /**
   * Récupérer un type par ID
   */
  async getById(id: string): Promise<TypeSignalement | null> {
    try {
      const docRef = doc(db, this.collectionName, id);
      const docSnap = await getDoc(docRef);
      
      if (docSnap.exists()) {
        const data = docSnap.data();
        return {
          id: data.id, // ID numérique du document
          code: data.code,
          nom: data.nom,
          icon: data.icon
        } as TypeSignalement;
      }
      return null;
    } catch (error) {
      console.error('Erreur lors de la récupération du type:', error);
      throw new Error('Impossible de récupérer le type de signalement');
    }
  }

  /**
   * Récupérer tous les types de signalement
   */
  async getAll(): Promise<TypeSignalement[]> {
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
          icon: data.icon
        } as TypeSignalement;
      });
    } catch (error) {
      console.error('Erreur lors de la récupération des types:', error);
      throw new Error('Impossible de récupérer les types de signalement');
    }
  }

  /**
   * Rechercher des types par nom
   */
  async searchByName(searchTerm: string): Promise<TypeSignalement[]> {
    try {
      // Firestore ne supporte pas la recherche full-text native
      // On utilise une approche simple avec des requêtes range
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
          icon: data.icon
        } as TypeSignalement;
      });
    } catch (error) {
      console.error('Erreur lors de la recherche:', error);
      throw new Error('Impossible de rechercher les types');
    }
  }

}