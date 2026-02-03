<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="custom-toolbar">
        <div class="header-content">
          <div class="profile-section">
            <div class="profile-avatar">
              <img 
                src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&h=100&fit=crop&crop=face" 
                alt="Photo de profil" 
                class="avatar-image"
              />
            </div>
            <div class="profile-info">
              <h1 class="page-title">Mes Signalements</h1>
              <div class="stats-inline" v-if="!isLoading && signalements.length > 0">
                <div class="stat-inline-item">
                  <ion-icon :icon="documentTextOutline" class="stat-inline-icon"></ion-icon>
                  <span class="stat-inline-text">{{ signalements.length }} signalement{{ signalements.length > 1 ? 's' : '' }}</span>
                </div>
                <div class="stat-separator">•</div>
                <div class="stat-inline-item">
                  <ion-icon :icon="timeOutline" class="stat-inline-icon"></ion-icon>
                  <span class="stat-inline-text">{{ recentSignalements }} cette semaine</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </ion-toolbar>
    </ion-header>
    
    <ion-content>

      <!-- État de chargement -->
      <div v-if="isLoading" class="loading-container">
        <ion-spinner name="dots" class="loading-spinner"></ion-spinner>
        <p class="loading-text">Chargement de vos signalements...</p>
      </div>

      <!-- Liste des signalements -->
      <div v-if="!isLoading && signalements.length > 0" class="signalements-container">
        <!-- Liste -->
        <div class="signalements-list">
          <div 
            v-for="signalement in signalements" 
            :key="signalement.id"
            @click="openSignalementDetail(signalement)"
            class="signalement-item"
          >
            <div class="item-icon">
              <ion-icon :icon="getTypeIcon(signalement.typeSignalementNom)" class="type-icon"></ion-icon>
            </div>
            
            <div class="item-content">
              <div class="item-header">
                <h3 class="item-title">{{ signalement.typeSignalementNom || 'Signalement' }}</h3>
                <span class="item-date">{{ formatDate(signalement.dateSignalement) }}</span>
              </div>
              
              <div class="item-details">
                <div v-if="signalement.entrepriseNom" class="detail-row">
                  <ion-icon :icon="businessOutline" class="detail-icon"></ion-icon>
                  <span class="detail-text">{{ signalement.entrepriseNom }}</span>
                </div>
                
                <div class="detail-row">
                  <ion-icon :icon="locationOutline" class="detail-icon"></ion-icon>
                  <span class="detail-text">
                    {{ signalement.latitude.toFixed(4) }}, {{ signalement.longitude.toFixed(4) }}
                  </span>
                </div>
              </div>
              
              <!-- <p v-if="signalement.description" class="item-description">
                {{ truncateText(signalement.description, 100) }}
              </p> -->
            </div>
            
            <div class="item-arrow">
              <ion-icon :icon="chevronForwardOutline" class="arrow-icon"></ion-icon>
            </div>
          </div>
        </div>
      </div>

      <!-- État vide -->
      <div v-else class="empty-state">
        <div class="empty-icon">
          <ion-icon :icon="documentTextOutline" class="empty-state-icon"></ion-icon>
        </div>
        <h2 class="empty-title">Aucun signalement</h2>
        <p class="empty-description">
          Vous n'avez encore créé aucun signalement.<br>
          Rendez-vous sur la carte pour signaler un problème.
        </p>
        <ion-button 
          @click="$router.push('/tabs/carte')"
          class="empty-action-btn"
          fill="solid"
          shape="round"
        >
          <ion-icon :icon="mapOutline" slot="start"></ion-icon>
          Aller à la carte
        </ion-button>
      </div>

      <!-- Détails du signalement -->
      <DetailSignalement 
        v-if="selectedSignalement"
        :signalement="selectedSignalement"
        :is-open="showSignalementDetail"
        @close="closeSignalementDetail"
      />
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage, 
  IonHeader, 
  IonToolbar, 
  IonTitle, 
  IonContent,
  IonSpinner,
  IonButton,
  IonIcon
} from '@ionic/vue';
import {
  documentTextOutline,
  timeOutline,
  businessOutline,
  locationOutline,
  chevronForwardOutline,
  mapOutline,
  alertCircleOutline,
  warningOutline,
  constructOutline
} from 'ionicons/icons';

import { signalementService } from '@/services/signalement';
import { auth } from '@/firebase';
import { onAuthStateChanged, type User } from 'firebase/auth';
import type { Signalement } from '@/services/signalement/types';

import DetailSignalement from '@/components/signalement/DetailSignalement.vue';

const router = useRouter();

// État
const signalements = ref<Signalement[]>([]);
const selectedSignalement = ref<Signalement | null>(null);
const showSignalementDetail = ref(false);
const isLoading = ref(true);
const currentUser = ref<User | null>(null);

// Computed
const recentSignalements = computed(() => {
  const oneWeekAgo = new Date();
  oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
  
  return signalements.value.filter(s => {
    const signalementDate = new Date(s.dateSignalement);
    return signalementDate >= oneWeekAgo;
  }).length;
});

// Fonctions utilitaires
const getTypeIcon = (typeName?: string) => {
  if (!typeName) return alertCircleOutline;
  
  const type = typeName.toLowerCase();
  if (type.includes('nid') || type.includes('trou')) return warningOutline;
  if (type.includes('route') || type.includes('chaussée')) return constructOutline;
  return alertCircleOutline;
};

const formatDate = (date: Date | string) => {
  const d = new Date(date);
  const now = new Date();
  const diffTime = Math.abs(now.getTime() - d.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays === 1) return 'Aujourd\'hui';
  if (diffDays === 2) return 'Hier';
  if (diffDays <= 7) return `Il y a ${diffDays - 1} jour${diffDays > 2 ? 's' : ''}`;
  
  return d.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: d.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  });
};

const truncateText = (text: string, maxLength: number) => {
  if (!text) return '';
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

// Fonctions principales
const loadUserSignalements = async () => {
  if (!currentUser.value?.uid) {
    isLoading.value = false;
    return;
  }
  
  try {
    isLoading.value = true;
    const data = await signalementService.getByUser(currentUser.value.uid);
    signalements.value = data;
  } catch (error) {
    console.error('Erreur lors du chargement des signalements:', error);
    signalements.value = [];
  } finally {
    isLoading.value = false;
  }
};

const openSignalementDetail = (signalement: Signalement) => {
  selectedSignalement.value = signalement;
  showSignalementDetail.value = true;
};

const closeSignalementDetail = () => {
  showSignalementDetail.value = false;
  selectedSignalement.value = null;
};

// Lifecycle
onMounted(() => {
  // Écouter les changements d'authentification
  onAuthStateChanged(auth, (user) => {
    currentUser.value = user;
    if (user) {
      loadUserSignalements();
    } else {
      signalements.value = [];
      isLoading.value = false;
    }
  });
});
</script>

<style scoped>
/* Header personnalisé */
.custom-toolbar {
  --background: #000000 !important;
  background: #000000 !important;
  --color: white;
  --min-height: 110px;
  --padding-top: env(safe-area-inset-top, 0);
  --padding-start: 0;
  --padding-end: 0;
}

.header-content {
  display: flex;
  align-items: center;
  padding: 16px 20px;
  width: 100%;
}

.profile-section {
  display: flex;
  align-items: center;
  gap: 16px;
  width: 100%;
}

.profile-avatar {
  position: relative;
  flex-shrink: 0;
}

.avatar-image {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: 3px solid rgba(255, 255, 255, 0.3);
  object-fit: cover;
  background: rgba(255, 255, 255, 0.1);
}

.profile-info {
  flex: 1;
  min-width: 0;
}

.page-title {
  font-weight: 700;
  font-size: 24px;
  margin: 0 0 8px 0;
  color: #0067E3;
  line-height: 1.2;
}

.stats-inline {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.stat-inline-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.stat-inline-icon {
  font-size: 16px;
  color: #0067E3;
}

.stat-inline-text {
  font-size: 14px;
  color: #0067E3;
  font-weight: 500;
  white-space: nowrap;
}

.stat-separator {
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  font-weight: 600;
}

.large-title {
  color: #007AFF;
  font-weight: 700;
}

ion-content {
  --background: #f5f5f7;
}

/* Loading */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
  gap: 16px;
}

.loading-spinner {
  --color: #007AFF;
  transform: scale(1.2);
}

.loading-text {
  color: #666;
  font-size: 16px;
  margin: 0;
}

/* Container principal */
.signalements-container {
  padding: 16px;
}

/* Liste des signalements */
.signalements-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.signalement-item {
  background: #ffffff;
  border-radius: 14px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.signalement-item:active {
  transform: scale(0.98);
  background: #f8f8f8;
}

.item-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #FF6B6B 0%, #EE5A5A 100%);
  border-radius: 12px;
  flex-shrink: 0;
}

.type-icon {
  font-size: 22px;
  color: white;
}

.item-content {
  flex: 1;
  min-width: 0;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
  gap: 8px;
}

.item-title {
  font-size: 15px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-date {
  font-size: 12px;
  color: #007AFF;
  font-weight: 500;
  white-space: nowrap;
  flex-shrink: 0;
}

.item-details {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.detail-row {
  display: flex;
  align-items: center;
  gap: 4px;
}

.detail-icon {
  font-size: 13px;
  color: #8E8E93;
  flex-shrink: 0;
}

.detail-text {
  font-size: 12px;
  color: #8E8E93;
  line-height: 1.3;
}

.item-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.arrow-icon {
  font-size: 18px;
  color: #C7C7CC;
}

/* État vide */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 60vh;
  padding: 40px 20px;
  text-align: center;
}

.empty-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 50%;
  margin-bottom: 24px;
}

.empty-state-icon {
  font-size: 40px;
  color: #007AFF;
}

.empty-title {
  font-size: 24px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 12px 0;
}

.empty-description {
  font-size: 16px;
  color: #666;
  line-height: 1.5;
  margin: 0 0 32px 0;
  max-width: 280px;
}

.empty-action-btn {
  --background: linear-gradient(135deg, #007AFF 0%, #0056CC 100%);
  --color: white;
  --border-radius: 24px;
  --padding-start: 24px;
  --padding-end: 24px;
  --padding-top: 12px;
  --padding-bottom: 12px;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 480px) {
  .signalements-container {
    padding: 12px;
  }
  
  .stats-content {
    flex-direction: column;
    gap: 16px;
  }
  
  .signalement-item {
    padding: 12px;
  }
  
  .item-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
  
  .item-date {
    align-self: flex-end;
  }
}
</style>
