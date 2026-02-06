// Export all types
export * from './types';

// Export all services
export { SignalementService } from './SignalementService';
export { TypeSignalementService } from './TypeSignalementService';
export { EntrepriseService } from './EntrepriseService';
export { PhotoService } from './PhotoService';

// Create singleton instances for easy use
import { SignalementService } from './SignalementService';
import { TypeSignalementService } from './TypeSignalementService';
import { EntrepriseService } from './EntrepriseService';
import { PhotoService } from './PhotoService';

export const signalementService = new SignalementService();
export const typeSignalementService = new TypeSignalementService();
export const entrepriseService = new EntrepriseService();
export const photoService = new PhotoService();

// Legacy export for backward compatibility
export default {
  signalement: signalementService,
  typeSignalement: typeSignalementService,
  entreprise: entrepriseService,
  photo: photoService
};