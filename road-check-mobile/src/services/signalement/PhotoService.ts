import { supabase } from '@/supabase';

const BUCKET_NAME = 'photos-signalement';

interface ImageOptimizationOptions {
  maxWidth?: number;
  maxHeight?: number;
  quality?: number; 
  format?: 'jpeg' | 'webp' | 'png';
  maxSizeKB?: number; 
}

export class PhotoService {

  async optimizeImage(file: File, options: ImageOptimizationOptions = {}): Promise<File> {
    const {
      maxWidth = 1920,
      maxHeight = 1080,
      quality = 0.85,
      format = 'jpeg',
      maxSizeKB = 1024 // 1MB max
    } = options;

    return new Promise((resolve, reject) => {
      const img = new Image();
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');

      if (!ctx) {
        reject(new Error('Canvas non supporté'));
        return;
      }

      img.onload = async () => {
        try {
          // Calcul des nouvelles dimensions (conserve le ratio)
          let { width, height } = this.calculateOptimalSize(img.width, img.height, maxWidth, maxHeight);

          // Ajustement si la taille dépasse maxSizeKB
          const optimizedFile = await this.compressToSize(canvas, ctx, img, width, height, quality, format, maxSizeKB);
          resolve(optimizedFile);
        } catch (error) {
          reject(error);
        }
      };

      img.onerror = () => reject(new Error('Erreur chargement image'));
      img.src = URL.createObjectURL(file);
    });
  }

  /**
   * Calcule la taille optimale en conservant le ratio d'aspect
   */
  private calculateOptimalSize(originalWidth: number, originalHeight: number, maxWidth: number, maxHeight: number): { width: number, height: number } {
    let width = originalWidth;
    let height = originalHeight;

    // Redimensionner si trop grand
    if (width > maxWidth) {
      height = (height * maxWidth) / width;
      width = maxWidth;
    }

    if (height > maxHeight) {
      width = (width * maxHeight) / height;
      height = maxHeight;
    }

    return { width: Math.round(width), height: Math.round(height) };
  }

  /**
   * Compresse l'image jusqu'à atteindre la taille maximale souhaitée
   */
  private async compressToSize(
    canvas: HTMLCanvasElement,
    ctx: CanvasRenderingContext2D,
    img: HTMLImageElement,
    width: number,
    height: number,
    initialQuality: number,
    format: string,
    maxSizeKB: number
  ): Promise<File> {
    let quality = initialQuality;
    let attempts = 0;
    const maxAttempts = 5;

    const tryCompression = async (): Promise<File> => {
      attempts++;

      // Redessiner l'image sur le canvas
      canvas.width = width;
      canvas.height = height;
      ctx.drawImage(img, 0, 0, width, height);

      // Convertir en blob de manière asynchrone
      const mimeType = format === 'webp' ? 'image/webp' : format === 'png' ? 'image/png' : 'image/jpeg';
      const blob = await new Promise<Blob>((resolve, reject) => {
        canvas.toBlob((blob) => {
          if (blob) {
            resolve(blob);
          } else {
            reject(new Error('Erreur conversion blob'));
          }
        }, mimeType, quality);
      });

      // Vérifier la taille
      const sizeKB = blob.size / 1024;
      if (sizeKB <= maxSizeKB || attempts >= maxAttempts) {
        // Taille OK ou max tentatives atteintes
        const optimizedFile = new File([blob], `optimized_${Date.now()}.${format}`, { type: mimeType });
        return optimizedFile;
      } else {
        // Réduire la qualité et réessayer
        quality = Math.max(0.1, quality - 0.1);
        return tryCompression();
      }
    };

    return tryCompression();
  }

  /**
   * Optimise plusieurs images en parallèle
   */
  async optimizeImages(files: File[], options?: ImageOptimizationOptions): Promise<File[]> {
    const promises = files.map(file => this.optimizeImage(file, options));
    return Promise.all(promises);
  }

  /**
   * Upload des photos vers Supabase Storage (avec optimisation automatique)
   * Les photos sont stockées dans : photos-signalement/{signalementId}/
   */
  async uploadPhotos(signalementId: string, photos: File[], optimize: boolean = true): Promise<string[]> {
    if (!photos || photos.length === 0) return [];

    // Optimiser les photos si demandé
    let photosToUpload = photos;
    if (optimize) {
      console.log('Optimisation des photos...');
      try {
        photosToUpload = await this.optimizeImages(photos, {
          maxWidth: 1920,
          maxHeight: 1080,
          quality: 0.85,
          format: 'jpeg',
          maxSizeKB: 800
        });
        console.log(`${photos.length} photo(s) optimisée(s)`);
      } catch (error) {
        console.warn('Erreur optimisation, upload des originaux:', error);
      }
    }

    const uploadedUrls: string[] = [];

    for (let i = 0; i < photosToUpload.length; i++) {
      const photo = photosToUpload[i];
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
   * Upload des photos SANS optimisation (pour cas spéciaux)
   */
  async uploadPhotosRaw(signalementId: string, photos: File[]): Promise<string[]> {
    return this.uploadPhotos(signalementId, photos, false);
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

  /**
   * Obtenir les statistiques d'optimisation pour une image
   */
  getOptimizationStats(originalFile: File, optimizedFile: File) {
    const originalSize = originalFile.size / 1024; // KB
    const optimizedSize = optimizedFile.size / 1024; // KB
    const reduction = ((originalSize - optimizedSize) / originalSize) * 100;

    return {
      originalSize: `${originalSize.toFixed(1)} KB`,
      optimizedSize: `${optimizedSize.toFixed(1)} KB`,
      reduction: `${reduction.toFixed(1)}%`,
      savedBytes: originalFile.size - optimizedFile.size
    };
  }

}

export const photoService = new PhotoService();
