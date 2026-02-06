import { supabase } from '@/supabase';

const BUCKET_NAME = 'photos-signalement';

export class PhotoService {
  /**
   * Upload des photos vers Supabase Storage
   * Les photos sont stockées dans : photos-signalement/{signalementId}/
   */
  async uploadPhotos(signalementId: string, photos: File[]): Promise<string[]> {
    if (!photos || photos.length === 0) return [];

    const uploadedUrls: string[] = [];

    for (let i = 0; i < photos.length; i++) {
      const photo = photos[i];
      const fileExt = photo.name.split('.').pop() || 'jpg';
      const fileName = `${Date.now()}_${i}.${fileExt}`;
      const filePath = `${signalementId}/${fileName}`;

      try {
        const { data, error } = await supabase.storage
          .from(BUCKET_NAME)
          .upload(filePath, photo, {
            cacheControl: '3600',
            upsert: false
          });

        if (error) {
          console.error(`Erreur upload photo ${i + 1}:`, error.message);
          continue;
        }

        // Récupérer une URL signée (valide 10 ans)
        const { data: urlData, error: urlError } = await supabase.storage
          .from(BUCKET_NAME)
          .createSignedUrl(filePath, 60 * 60 * 24 * 365 * 10);

        if (urlError) {
          console.error(`Erreur URL signée photo ${i + 1}:`, urlError.message);
          continue;
        }

        if (urlData?.signedUrl) {
          uploadedUrls.push(urlData.signedUrl);
          console.log(`Photo ${i + 1} uploadée:`, urlData.signedUrl);
        }
      } catch (err) {
        console.error(`Erreur inattendue upload photo ${i + 1}:`, err);
      }
    }

    return uploadedUrls;
  }

  /**
   * Supprimer toutes les photos d'un signalement
   */
  async deletePhotos(signalementId: string): Promise<void> {
    try {
      const { data: files, error: listError } = await supabase.storage
        .from(BUCKET_NAME)
        .list(signalementId);

      if (listError) {
        console.error('Erreur listing photos:', listError.message);
        return;
      }

      if (files && files.length > 0) {
        const filePaths = files.map(f => `${signalementId}/${f.name}`);
        const { error: deleteError } = await supabase.storage
          .from(BUCKET_NAME)
          .remove(filePaths);

        if (deleteError) {
          console.error('Erreur suppression photos:', deleteError.message);
        }
      }
    } catch (err) {
      console.error('Erreur suppression photos:', err);
    }
  }
}

export const photoService = new PhotoService();
