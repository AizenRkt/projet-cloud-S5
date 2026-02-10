import { Geolocation, PositionOptions, Position } from "@capacitor/geolocation";

export type GeoError = 'permission_denied' | 'location_disabled' | 'timeout' | 'unknown';

export interface GeoResult {
  position: Position | null;
  error?: GeoError;
  errorMessage?: string;
}

export default class GeolocalisationService {

  /**
   * Vérifie si la permission de localisation est accordée
   */
  static async checkPermission(): Promise<'granted' | 'denied' | 'prompt'> {
    try {
      const status = await Geolocation.checkPermissions();
      return status.location === 'granted' ? 'granted' : 
             status.location === 'denied' ? 'denied' : 'prompt';
    } catch {
      return 'prompt';
    }
  }

  /**
   * Demande la permission de localisation
   */
  static async requestPermission(): Promise<boolean> {
    try {
      const permission = await Geolocation.requestPermissions();
      return permission.location === 'granted';
    } catch {
      return false;
    }
  }

  /**
   * Récupère la position actuelle avec gestion d'erreurs détaillée
   */
  static async getCurrentPositionDetailed(): Promise<GeoResult> {
    try {
      // Vérifier/demander la permission
      const permission = await Geolocation.requestPermissions();
      if (permission.location !== 'granted') {
        return {
          position: null,
          error: 'permission_denied',
          errorMessage: 'La permission de localisation a été refusée. Activez-la dans les paramètres de l\'application.'
        };
      }

      // Tenter de récupérer la position
      const position = await Geolocation.getCurrentPosition({
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 0
      } as PositionOptions);

      return { position };

    } catch (err: any) {
      const errorMessage = err?.message || String(err);

      // Détecter si le GPS/localisation est désactivé
      if (
        errorMessage.includes('location disabled') ||
        errorMessage.includes('location services') ||
        errorMessage.includes('Location services are not enabled') ||
        errorMessage.includes('denied') === false && errorMessage.includes('permission') === false &&
        (err?.code === 2 || err?.code === 1)
      ) {
        return {
          position: null,
          error: 'location_disabled',
          errorMessage: 'La localisation (GPS) est désactivée sur votre appareil. Veuillez l\'activer dans les paramètres.'
        };
      }

      if (errorMessage.includes('timeout') || err?.code === 3) {
        return {
          position: null,
          error: 'timeout',
          errorMessage: 'Impossible d\'obtenir votre position. Vérifiez que le GPS est activé et réessayez.'
        };
      }

      console.error("Impossible d'obtenir la position :", err);
      return {
        position: null,
        error: 'unknown',
        errorMessage: 'Une erreur est survenue lors de la récupération de votre position.'
      };
    }
  }

  /**
   * Récupère la position actuelle (compatibilité ascendante)
   */
  static async getCurrentPosition(): Promise<Position | null> {
    const result = await this.getCurrentPositionDetailed();
    return result.position;
  }

  static async watchPosition(
    callback: (position: Position | null, err?: any) => void
  ): Promise<string> {
    const permission = await Geolocation.requestPermissions();
    if (permission.location !== 'granted') return "-1";

    const watchId = await Geolocation.watchPosition(
      { enableHighAccuracy: true },
      (position, err) => callback(position, err)
    );
    return watchId;  
  }

  static async clearWatch(watchId: string) {
    await Geolocation.clearWatch({ id: watchId });
  }
}
