<template>
  <ion-modal
    :is-open="isOpen"
    :can-dismiss="canDismiss"
    @didDismiss="$emit('dismiss')"
    class="permission-modal"
  >
    <div class="permission-container">
      <!-- Header -->
      <div class="permission-header">
        <div class="header-icon">
          <ion-icon :icon="shieldCheckmarkOutline" class="shield-icon"></ion-icon>
        </div>
        <h2 class="header-title">Autorisations requises</h2>
        <p class="header-subtitle">
          Pour une expérience optimale, votre application a besoin de quelques autorisations
        </p>
      </div>

      <!-- Liste des permissions -->
      <div class="permissions-list">
        <div 
          v-for="permission in permissions" 
          :key="permission.type"
          class="permission-item"
          :class="{
            'granted': getPermissionStatus(permission.type) === 'granted',
            'denied': getPermissionStatus(permission.type) === 'denied',
            'required': permission.required
          }"
        >
          <div class="permission-icon">
            <ion-icon 
              :icon="getPermissionIcon(permission.icon)" 
              class="item-icon"
            ></ion-icon>
          </div>
          
          <div class="permission-content">
            <div class="permission-header-item">
              <h3 class="permission-title">{{ permission.title }}</h3>
              <div class="permission-badges">
                <span v-if="permission.required" class="required-badge">Requis</span>
                <span 
                  class="status-badge" 
                  :class="getPermissionStatus(permission.type)"
                >
                  {{ getStatusLabel(getPermissionStatus(permission.type)) }}
                </span>
              </div>
            </div>
            <p class="permission-description">{{ permission.description }}</p>
          </div>
          
          <div class="permission-action">
            <ion-button
              v-if="getPermissionStatus(permission.type) !== 'granted'"
              @click="requestSinglePermission(permission.type)"
              :disabled="isRequesting === permission.type || (!getPermissionCanAskAgain(permission.type) && getPermissionStatus(permission.type) === 'denied')"
              size="small"
              :fill="permission.required ? 'solid' : 'outline'"
              :color="permission.required ? 'danger' : 'primary'"
            >
              <ion-spinner 
                v-if="isRequesting === permission.type" 
                name="dots" 
                class="spinner"
              ></ion-spinner>
              <span v-else-if="!getPermissionCanAskAgain(permission.type) && getPermissionStatus(permission.type) === 'denied'">
                Refusé
              </span>
              <span v-else>
                {{ permission.required ? 'Autoriser' : 'Demander' }}
              </span>
            </ion-button>
            <ion-icon 
              v-else
              :icon="checkmarkCircleOutline" 
              class="granted-icon"
            ></ion-icon>
          </div>
        </div>
      </div>

      <!-- Actions principales -->
      <div class="permission-actions">
        <!-- Message d'erreur si permissions requises refusées -->
        <div v-if="hasRequiredDenied" class="error-message">
          <ion-icon :icon="warningOutline" class="warning-icon"></ion-icon>
          <div class="error-content">
            <h4 class="error-title">Permissions requises manquantes</h4>
            <p class="error-text">
              Certaines permissions essentielles ont été refusées. 
              L'application ne pourra pas fonctionner correctement sans ces autorisations.
            </p>
            <ion-button 
              @click="openSettings" 
              size="small" 
              fill="clear" 
              class="settings-btn"
            >
              Ouvrir les paramètres
            </ion-button>
          </div>
        </div>

        <!-- Boutons d'action -->
        <div class="action-buttons">
          <ion-button
            v-if="!hasRequiredDenied"
            @click="skipOptionalPermissions"
            fill="clear"
            color="medium"
            :disabled="isRequesting !== null"
          >
            Ignorer les optionnelles
          </ion-button>
          
          <ion-button
            @click="requestAllPermissions"
            :disabled="isRequesting !== null || allPermissionsGranted"
            class="primary-action"
          >
            <ion-spinner 
              v-if="isRequesting === 'all'" 
              name="dots" 
              class="spinner"
            ></ion-spinner>
            <span v-else-if="allPermissionsGranted">
              Toutes accordées
            </span>
            <span v-else>
              Tout autoriser
            </span>
          </ion-button>
          
          <ion-button
            v-if="canContinue"
            @click="continueToApp"
            color="success"
            class="continue-btn"
          >
            Continuer
          </ion-button>
        </div>
      </div>
    </div>
  </ion-modal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { 
  IonModal, 
  IonButton, 
  IonIcon, 
  IonSpinner 
} from '@ionic/vue';
import {
  shieldCheckmarkOutline,
  checkmarkCircleOutline,
  warningOutline,
  locationOutline,
  cameraOutline,
  notificationsOutline,
  micOutline,
  imagesOutline
} from 'ionicons/icons';
import { permissionService, type PermissionType, type PermissionRequest, type PermissionResult } from '@/services/permissions';

interface Props {
  isOpen: boolean;
  canDismiss?: boolean;
  requiredOnly?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  canDismiss: true,
  requiredOnly: false
});

const emit = defineEmits<{
  dismiss: [];
  completed: [results: PermissionResult[]];
  requiredGranted: [];
}>();

// État
const permissionResults = ref<PermissionResult[]>([]);
const isRequesting = ref<PermissionType | 'all' | null>(null);

// Permissions à afficher
const permissions = computed(() => {
  if (props.requiredOnly) {
    return permissionService.getRequiredPermissions();
  }
  return permissionService.getDefaultPermissions();
});

// États calculés
const allPermissionsGranted = computed(() => {
  return permissions.value.every(p => 
    getPermissionStatus(p.type) === 'granted'
  );
});

const hasRequiredDenied = computed(() => {
  const requiredPermissions = permissions.value.filter(p => p.required);
  return requiredPermissions.some(p => 
    getPermissionStatus(p.type) === 'denied' && 
    !getPermissionCanAskAgain(p.type)
  );
});

const canContinue = computed(() => {
  const requiredPermissions = permissions.value.filter(p => p.required);
  return requiredPermissions.every(p => 
    getPermissionStatus(p.type) === 'granted'
  );
});

// Fonctions utilitaires
const getPermissionStatus = (type: PermissionType): string => {
  const result = permissionResults.value.find(r => r.type === type);
  return result?.status || 'prompt';
};

const getPermissionCanAskAgain = (type: PermissionType): boolean => {
  const result = permissionResults.value.find(r => r.type === type);
  return result?.canAskAgain ?? true;
};

const getPermissionIcon = (iconName: string) => {
  const iconMap: Record<string, any> = {
    'location': locationOutline,
    'camera': cameraOutline,
    'notifications': notificationsOutline,
    'microphone': micOutline,
    'images': imagesOutline
  };
  return iconMap[iconName] || shieldCheckmarkOutline;
};

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'granted': return 'Accordée';
    case 'denied': return 'Refusée';
    case 'restricted': return 'Restreinte';
    default: return 'En attente';
  }
};

// Actions
const checkAllPermissions = async () => {
  const permissionTypes = permissions.value.map(p => p.type);
  const results = await permissionService.checkMultiplePermissions(permissionTypes);
  permissionResults.value = results.results;
};

const requestSinglePermission = async (type: PermissionType) => {
  isRequesting.value = type;
  
  try {
    const result = await permissionService.requestPermission(type);
    
    // Mettre à jour le résultat
    const index = permissionResults.value.findIndex(r => r.type === type);
    if (index >= 0) {
      permissionResults.value[index] = result;
    } else {
      permissionResults.value.push(result);
    }
  } catch (error) {
    console.error(`Erreur lors de la demande de permission ${type}:`, error);
  } finally {
    isRequesting.value = null;
  }
};

const requestAllPermissions = async () => {
  isRequesting.value = 'all';
  
  try {
    const permissionTypes = permissions.value
      .filter(p => getPermissionStatus(p.type) !== 'granted')
      .map(p => p.type);
    
    for (const type of permissionTypes) {
      await requestSinglePermission(type);
    }
    
    emit('completed', permissionResults.value);
    
    if (canContinue.value) {
      emit('requiredGranted');
    }
  } finally {
    isRequesting.value = null;
  }
};

const skipOptionalPermissions = () => {
  if (canContinue.value) {
    emit('requiredGranted');
  }
};

const continueToApp = () => {
  emit('completed', permissionResults.value);
  emit('requiredGranted');
};

const openSettings = async () => {
  await permissionService.openAppSettings();
};

// Lifecycle
onMounted(async () => {
  if (props.isOpen) {
    await checkAllPermissions();
  }
});

// Watchers
import { watch } from 'vue';
watch(() => props.isOpen, async (newValue) => {
  if (newValue) {
    await checkAllPermissions();
  }
});
</script>

<style scoped>
.permission-modal {
  --width: 90%;
  --max-width: 500px;
  --height: auto;
  --max-height: 90vh;
  --border-radius: 20px;
}

.permission-container {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  max-height: 90vh;
  overflow-y: auto;
}

/* Header */
.permission-header {
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.header-icon {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

.shield-icon {
  width: 32px;
  height: 32px;
  color: white;
}

.header-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

.header-subtitle {
  font-size: 16px;
  color: #666;
  margin: 0;
  line-height: 1.4;
}

/* Liste des permissions */
.permissions-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.permission-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 16px;
  border: 2px solid transparent;
  transition: all 0.2s ease;
}

.permission-item.granted {
  background: linear-gradient(135deg, #34C759 0.1%, #30D158 100%, rgba(52, 199, 89, 0.1));
  border-color: rgba(52, 199, 89, 0.2);
}

.permission-item.denied {
  background: linear-gradient(135deg, rgba(255, 59, 48, 0.05) 0%, rgba(255, 59, 48, 0.1) 100%);
  border-color: rgba(255, 59, 48, 0.2);
}

.permission-item.required {
  border-color: #007AFF;
}

.permission-icon {
  width: 48px;
  height: 48px;
  background: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.item-icon {
  width: 24px;
  height: 24px;
  color: #007AFF;
}

.permission-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.permission-header-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.permission-title {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.permission-badges {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}

.required-badge {
  background: #FF3B30;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.granted {
  background: #34C759;
  color: white;
}

.status-badge.denied {
  background: #FF3B30;
  color: white;
}

.status-badge.prompt {
  background: #FF9500;
  color: white;
}

.permission-description {
  font-size: 14px;
  color: #666;
  margin: 0;
  line-height: 1.4;
}

.permission-action {
  flex-shrink: 0;
}

.granted-icon {
  width: 32px;
  height: 32px;
  color: #34C759;
}

/* Message d'erreur */
.error-message {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: linear-gradient(135deg, rgba(255, 59, 48, 0.05) 0%, rgba(255, 59, 48, 0.1) 100%);
  border: 2px solid rgba(255, 59, 48, 0.2);
  border-radius: 16px;
}

.warning-icon {
  width: 24px;
  height: 24px;
  color: #FF3B30;
  flex-shrink: 0;
  margin-top: 2px;
}

.error-content {
  flex: 1;
}

.error-title {
  font-size: 14px;
  font-weight: 600;
  color: #FF3B30;
  margin: 0 0 4px 0;
}

.error-text {
  font-size: 13px;
  color: #666;
  margin: 0 0 8px 0;
  line-height: 1.4;
}

.settings-btn {
  --color: #FF3B30;
  font-size: 12px;
  font-weight: 600;
}

/* Actions */
.permission-actions {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.action-buttons {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.action-buttons > ion-button {
  flex: 1;
  min-width: 120px;
}

.primary-action {
  --background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
}

.continue-btn {
  --background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
}

.spinner {
  --color: white;
  transform: scale(0.8);
}

/* Responsive */
@media (max-width: 480px) {
  .permission-container {
    padding: 16px;
  }
  
  .permission-item {
    padding: 12px;
  }
  
  .permission-header-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
  
  .action-buttons {
    flex-direction: column;
  }
}
</style>