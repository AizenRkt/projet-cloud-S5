import { isPlatform } from '@ionic/vue';

// Types pour les permissions
export interface PermissionStatus {
  state: 'granted' | 'denied' | 'prompt' | 'restricted';
}

export type PermissionType = 
  | 'camera' 
  | 'geolocation' 
  | 'notifications' 
  | 'microphone'
  | 'photos';

export interface PermissionRequest {
  type: PermissionType;
  title: string;
  description: string;
  icon: string;
  required: boolean; // Si true, l'app ne peut pas fonctionner sans cette permission
}

export interface PermissionResult {
  type: PermissionType;
  status: 'granted' | 'denied' | 'prompt' | 'restricted';
  canAskAgain: boolean;
}

export interface PermissionCheckResult {
  allGranted: boolean;
  results: PermissionResult[];
  deniedRequired: PermissionResult[];
}

class PermissionService {
  private readonly defaultPermissions: PermissionRequest[] = [
    {
      type: 'geolocation',
      title: 'Localisation',
      description: 'Nécessaire pour localiser les signalements sur la carte',
      icon: 'location',
      required: true
    },
    {
      type: 'camera',
      title: 'Appareil photo',
      description: 'Pour prendre des photos des problèmes signalés',
      icon: 'camera',
      required: false
    },
    {
      type: 'notifications',
      title: 'Notifications',
      description: 'Pour recevoir des mises à jour sur vos signalements',
      icon: 'notifications',
      required: false
    },
    {
      type: 'photos',
      title: 'Galerie photo',
      description: 'Pour sélectionner des photos existantes',
      icon: 'images',
      required: false
    }
  ];

  /**
   * Vérifie le statut actuel d'une permission spécifique
   */
  async checkPermission(permissionType: PermissionType): Promise<PermissionResult> {
    try {
      let permissionStatus: PermissionState = 'prompt';
      
      // Utiliser l'API Web Permissions si disponible
      if ('permissions' in navigator) {
        try {
          let permissionName: PermissionName;
          
          switch (permissionType) {
            case 'geolocation':
              permissionName = 'geolocation' as PermissionName;
              break;
            case 'camera':
              permissionName = 'camera' as PermissionName;
              break;
            case 'notifications':
              permissionName = 'notifications' as PermissionName;
              break;
            case 'microphone':
              permissionName = 'microphone' as PermissionName;
              break;
            case 'photos':
              // Photos n'est pas directement supporté par l'API Web Permissions
              // On va utiliser la géolocalisation comme fallback ou retourner 'prompt'
              return {
                type: permissionType,
                status: 'prompt',
                canAskAgain: true
              };
            default:
              throw new Error(`Permission type ${permissionType} not supported`);
          }
          
          const result = await navigator.permissions.query({ name: permissionName });
          permissionStatus = result.state;
        } catch (error) {
          console.warn(`API Permissions non supportée pour ${permissionType}:`, error);
          permissionStatus = 'prompt';
        }
      }

      return {
        type: permissionType,
        status: permissionStatus === 'granted' ? 'granted' : 
               permissionStatus === 'denied' ? 'denied' : 'prompt',
        canAskAgain: permissionStatus !== 'denied' || !isPlatform('ios')
      };
    } catch (error) {
      console.error(`Erreur lors de la vérification de la permission ${permissionType}:`, error);
      return {
        type: permissionType,
        status: 'prompt',
        canAskAgain: true
      };
    }
  }

  /**
   * Demande une permission spécifique
   */
  async requestPermission(permissionType: PermissionType): Promise<PermissionResult> {
    try {
      let permissionStatus: PermissionState = 'prompt';

      switch (permissionType) {
        case 'geolocation':
          // Utiliser l'API Geolocation standard
          if ('geolocation' in navigator) {
            try {
              await new Promise<GeolocationPosition>((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                  timeout: 10000,
                  maximumAge: 0,
                  enableHighAccuracy: false
                });
              });
              permissionStatus = 'granted';
            } catch (error) {
              permissionStatus = 'denied';
            }
          }
          break;

        case 'camera':
          // Utiliser l'API getUserMedia pour la caméra
          if ('mediaDevices' in navigator) {
            try {
              const stream = await navigator.mediaDevices.getUserMedia({ video: true });
              stream.getTracks().forEach(track => track.stop()); // Stopper immédiatement
              permissionStatus = 'granted';
            } catch (error) {
              permissionStatus = 'denied';
            }
          }
          break;

        case 'microphone':
          // Utiliser l'API getUserMedia pour le microphone
          if ('mediaDevices' in navigator) {
            try {
              const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
              stream.getTracks().forEach(track => track.stop()); // Stopper immédiatement
              permissionStatus = 'granted';
            } catch (error) {
              permissionStatus = 'denied';
            }
          }
          break;

        case 'notifications':
          // Utiliser l'API Notifications standard
          if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            permissionStatus = permission === 'granted' ? 'granted' : 
                             permission === 'denied' ? 'denied' : 'prompt';
          }
          break;

        case 'photos':
          // Pour les photos, on simule une acceptation car c'est généralement géré par l'input file
          permissionStatus = 'granted';
          break;

        default:
          throw new Error(`Permission type ${permissionType} not supported`);
      }

      return {
        type: permissionType,
        status: permissionStatus === 'granted' ? 'granted' : 
               permissionStatus === 'denied' ? 'denied' : 'prompt',
        canAskAgain: permissionStatus !== 'denied' || !isPlatform('ios')
      };
    } catch (error) {
      console.error(`Erreur lors de la demande de permission ${permissionType}:`, error);
      return {
        type: permissionType,
        status: 'denied',
        canAskAgain: true
      };
    }
  }

  /**
   * Vérifie plusieurs permissions à la fois
   */
  async checkMultiplePermissions(permissionTypes: PermissionType[]): Promise<PermissionCheckResult> {
    const results: PermissionResult[] = [];
    
    for (const permissionType of permissionTypes) {
      const result = await this.checkPermission(permissionType);
      results.push(result);
    }

    const allGranted = results.every(result => result.status === 'granted');
    const deniedRequired = results.filter(result => {
      const permissionInfo = this.defaultPermissions.find(p => p.type === result.type);
      return permissionInfo?.required && result.status !== 'granted';
    });

    return {
      allGranted,
      results,
      deniedRequired
    };
  }

  /**
   * Demande toutes les permissions par défaut nécessaires à l'application
   */
  async requestAllDefaultPermissions(): Promise<PermissionCheckResult> {
    const permissionTypes = this.defaultPermissions.map(p => p.type);
    const results: PermissionResult[] = [];
    
    for (const permissionType of permissionTypes) {
      const result = await this.requestPermission(permissionType);
      results.push(result);
    }

    const allGranted = results.every(result => result.status === 'granted');
    const deniedRequired = results.filter(result => {
      const permissionInfo = this.defaultPermissions.find(p => p.type === result.type);
      return permissionInfo?.required && result.status !== 'granted';
    });

    return {
      allGranted,
      results,
      deniedRequired
    };
  }

  /**
   * Demande les permissions essentielles (marquées comme required)
   */
  async requestRequiredPermissions(): Promise<PermissionCheckResult> {
    const requiredPermissions = this.defaultPermissions
      .filter(p => p.required)
      .map(p => p.type);
    
    const results: PermissionResult[] = [];
    
    for (const permissionType of requiredPermissions) {
      const result = await this.requestPermission(permissionType);
      results.push(result);
    }

    const allGranted = results.every(result => result.status === 'granted');
    const deniedRequired = results.filter(result => result.status !== 'granted');

    return {
      allGranted,
      results,
      deniedRequired
    };
  }

  /**
   * Récupère les informations sur une permission
   */
  getPermissionInfo(permissionType: PermissionType): PermissionRequest | undefined {
    return this.defaultPermissions.find(p => p.type === permissionType);
  }

  /**
   * Récupère toutes les permissions par défaut
   */
  getDefaultPermissions(): PermissionRequest[] {
    return [...this.defaultPermissions];
  }

  /**
   * Récupère les permissions requises
   */
  getRequiredPermissions(): PermissionRequest[] {
    return this.defaultPermissions.filter(p => p.required);
  }

  /**
   * Récupère les permissions optionnelles
   */
  getOptionalPermissions(): PermissionRequest[] {
    return this.defaultPermissions.filter(p => !p.required);
  }

  /**
   * Vérifie si l'application peut fonctionner avec les permissions actuelles
   */
  async canAppFunction(): Promise<boolean> {
    const requiredPermissions = this.getRequiredPermissions().map(p => p.type);
    const results = await this.checkMultiplePermissions(requiredPermissions);
    return results.deniedRequired.length === 0;
  }

  /**
   * Ouvre les paramètres de l'application pour que l'utilisateur puisse activer les permissions
   */
  async openAppSettings(): Promise<void> {
    try {
      // Sur mobile, on peut ouvrir les paramètres de l'app
      if (isPlatform('capacitor')) {
        // Cette méthode dépend du plugin utilisé
        // On peut utiliser @capacitor-community/native-settings ou similar
        console.log('Redirection vers les paramètres de l\'application');
        // await NativeSettings.open({ optionAndroid: AndroidSettings.ApplicationDetails });
      } else {
        console.log('Ouverture des paramètres non disponible sur cette plateforme');
      }
    } catch (error) {
      console.error('Erreur lors de l\'ouverture des paramètres:', error);
    }
  }

  /**
   * Méthode utilitaire pour formater le message d'erreur de permission
   */
  getPermissionErrorMessage(permissionType: PermissionType): string {
    const info = this.getPermissionInfo(permissionType);
    if (!info) return 'Permission non reconnue';
    
    return `L'autorisation "${info.title}" est nécessaire pour ${info.description.toLowerCase()}. Vous pouvez l'activer dans les paramètres de l'application.`;
  }
}

// Export de l'instance singleton
export const permissionService = new PermissionService();

// Export par défaut
export default permissionService;