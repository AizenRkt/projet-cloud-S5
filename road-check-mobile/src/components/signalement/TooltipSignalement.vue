<template>
  <div class="tooltip-signalement">
    <div class="tooltip-header">
      <ion-icon :icon="getTypeIcon(signalement.typeSignalementNom)" class="type-icon"></ion-icon>
      <span class="type-name">{{ signalement.typeSignalementNom }}</span>
    </div>
    
    <div class="tooltip-content">
      <div class="entreprise-info" v-if="signalement.entrepriseNom">
        <ion-icon :icon="businessOutline" class="entreprise-icon"></ion-icon>
        <span>{{ signalement.entrepriseNom }}</span>
      </div>
      
      <div class="date-info">
        <ion-icon :icon="timeOutline" class="date-icon"></ion-icon>
        <span>{{ formatDate(signalement.dateSignalement) }}</span>
      </div>
    </div>
    
    <div class="tooltip-footer">
      <span class="tap-hint">Appuyez pour plus de détails</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { IonIcon } from '@ionic/vue';
import { 
  businessOutline, 
  timeOutline,
  alertCircleOutline,
  warningOutline,
  constructOutline
} from 'ionicons/icons';
import type { Signalement } from '@/services/signalement/types';

interface Props {
  signalement: Signalement;
}

defineProps<Props>();

// Icônes selon le type de signalement
const getTypeIcon = (typeName?: string) => {
  if (!typeName) return alertCircleOutline;
  
  const type = typeName.toLowerCase();
  if (type.includes('nid') || type.includes('trou')) return warningOutline;
  if (type.includes('route') || type.includes('chaussée')) return constructOutline;
  return alertCircleOutline;
};

// Formatage de la date
const formatDate = (date: Date | string) => {
  const d = new Date(date);
  return d.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
};
</script>

<style scoped>
.tooltip-signalement {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 12px;
  min-width: 200px;
  max-width: 280px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  font-size: 14px;
  color: #1a1a1a;
}

.tooltip-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.type-icon {
  font-size: 18px;
  color: #FF4444;
}

.type-name {
  font-weight: 600;
  color: #1a1a1a;
}

.tooltip-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 8px;
}

.entreprise-info,
.date-info {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #666;
}

.entreprise-icon,
.date-icon {
  font-size: 14px;
  color: #999;
}

.tooltip-footer {
  text-align: center;
  padding-top: 8px;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.tap-hint {
  font-size: 11px;
  color: #007AFF;
  font-style: italic;
}

/* Animation d'apparition */
.tooltip-signalement {
  animation: fadeInScale 0.2s ease-out;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>