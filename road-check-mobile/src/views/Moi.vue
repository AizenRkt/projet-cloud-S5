<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="custom-toolbar">
        <div class="header-content">
          <div class="profile-section">
            <div class="profile-avatar" @click="openProfilSidebar">
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
        <!-- Barre de recherche et filtres -->
        <div class="search-filter-section">
          <!-- Barre de recherche -->
          <div class="search-container">
            <ion-searchbar
              v-model="searchText"
              placeholder="Rechercher un signalement..."
              :debounce="300"
              class="custom-searchbar"
            ></ion-searchbar>
          </div>
          
          <!-- Filtres -->
          <div class="filters-container">
            <div class="filter-row">
              <!-- Filtre par type -->
              <div class="filter-item">
                <ion-select
                  v-model="filterType"
                  placeholder="Type"
                  interface="action-sheet"
                  class="filter-select"
                >
                  <ion-select-option value="">Tous les types</ion-select-option>
                  <ion-select-option
                    v-for="type in typeOptions"
                    :key="type"
                    :value="type"
                  >
                    {{ type }}
                  </ion-select-option>
                </ion-select>
              </div>
              
              <!-- Filtre par entreprise -->
              <div class="filter-item">
                <ion-select
                  v-model="filterEntreprise"
                  placeholder="Entreprise"
                  interface="action-sheet"
                  class="filter-select"
                >
                  <ion-select-option value="">Toutes les entreprises</ion-select-option>
                  <ion-select-option
                    v-for="entreprise in entrepriseOptions"
                    :key="entreprise"
                    :value="entreprise"
                  >
                    {{ entreprise }}
                  </ion-select-option>
                </ion-select>
              </div>
              
              <!-- Filtre par date -->
              <div class="filter-item">
                <ion-select
                  v-model="filterDate"
                  placeholder="Période"
                  interface="action-sheet"
                  class="filter-select"
                >
                  <ion-select-option value="">Toutes les dates</ion-select-option>
                  <ion-select-option value="recent">Aujourd'hui</ion-select-option>
                  <ion-select-option value="week">Cette semaine</ion-select-option>
                  <ion-select-option value="month">Ce mois</ion-select-option>
                </ion-select>
              </div>
            </div>
            
            <!-- Filtres actifs -->
            <div v-if="filterType || filterEntreprise || filterDate" class="active-filters">
              <ion-chip 
                v-if="filterType" 
                @click="removeFilter('type')"
                class="filter-chip"
              >
                <ion-label>{{ filterType }}</ion-label>
                <ion-icon :icon="closeOutline"></ion-icon>
              </ion-chip>
              
              <ion-chip 
                v-if="filterEntreprise" 
                @click="removeFilter('entreprise')"
                class="filter-chip"
              >
                <ion-label>{{ filterEntreprise }}</ion-label>
                <ion-icon :icon="closeOutline"></ion-icon>
              </ion-chip>
              
              <ion-chip 
                v-if="filterDate" 
                @click="removeFilter('date')"
                class="filter-chip"
              >
                <ion-label>{{ getDateFilterLabel(filterDate) }}</ion-label>
                <ion-icon :icon="closeOutline"></ion-icon>
              </ion-chip>
              
              <button 
                @click="clearFilters"
                class="clear-filters-btn"
              >
                Effacer tout
              </button>
            </div>
          </div>
          
          <!-- Résultats -->
          <div class="results-info">
            <span class="results-count">
              {{ filteredSignalements.length }} signalement{{ filteredSignalements.length > 1 ? 's' : '' }}
              {{ filteredSignalements.length !== signalements.length ? ` sur ${signalements.length}` : '' }}
            </span>
          </div>
        </div>

        <!-- Liste -->
        <div v-if="filteredSignalements.length > 0" class="signalements-list">
          <div 
            v-for="signalement in filteredSignalements" 
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
        
        <!-- Aucun résultat après filtrage -->
        <div v-else class="no-results">
          <div class="no-results-icon">
            <ion-icon :icon="searchOutline" class="no-results-icon-svg"></ion-icon>
          </div>
          <h3 class="no-results-title">Aucun résultat</h3>
          <p class="no-results-description">
            Aucun signalement ne correspond à vos critères de recherche.
          </p>
          <button @click="clearFilters" class="clear-filters-btn-alt">
            Effacer les filtres
          </button>
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

      <!-- Sidebar de profil -->
      <ProfilSidebar 
        :is-open="showProfilSidebar"
        :current-user="currentUser"
        :signalements="signalements"
        :recent-count="recentSignalements"
        @close="closeProfilSidebar"
        @logout="handleLogout"
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
  IonIcon,
  IonSearchbar,
  IonSelect,
  IonSelectOption,
  IonChip,
  IonLabel
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
  constructOutline,
  searchOutline,
  filterOutline,
  closeOutline
} from 'ionicons/icons';

import { signalementService, typeSignalementService, entrepriseService } from '@/services/signalement';
import { auth } from '@/firebase';
import { onAuthStateChanged, type User } from 'firebase/auth';
import type { Signalement, TypeSignalement, Entreprise } from '@/services/signalement/types';

import DetailSignalement from '@/components/signalement/DetailSignalement.vue';
import ProfilSidebar from '@/components/ProfilSidebar.vue';

const router = useRouter();

// État
const signalements = ref<Signalement[]>([]);
const selectedSignalement = ref<Signalement | null>(null);
const showSignalementDetail = ref(false);
const isLoading = ref(true);
const currentUser = ref<User | null>(null);
const showProfilSidebar = ref(false);

// États pour les filtres
const searchText = ref('');
const filterType = ref<string>('');
const filterEntreprise = ref<string>('');
const filterDate = ref<string>(''); // 'recent', 'week', 'month', 'all'

// Données complètes pour les filtres
const allTypes = ref<TypeSignalement[]>([]);
const allEntreprises = ref<Entreprise[]>([]);
const isLoadingFilters = ref(true);

// Computed
const recentSignalements = computed(() => {
  const oneWeekAgo = new Date();
  oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
  
  return signalements.value.filter(s => {
    const signalementDate = new Date(s.dateSignalement);
    return signalementDate >= oneWeekAgo;
  }).length;
});

// Signalements filtrés
const filteredSignalements = computed(() => {
  let filtered = [...signalements.value];
  
  // Filtre par texte de recherche
  if (searchText.value.trim()) {
    const search = searchText.value.toLowerCase().trim();
    filtered = filtered.filter(s => 
      s.typeSignalementNom?.toLowerCase().includes(search) ||
      s.entrepriseNom?.toLowerCase().includes(search) ||
      s.description?.toLowerCase().includes(search)
    );
  }
  
  // Filtre par type
  if (filterType.value) {
    filtered = filtered.filter(s => s.typeSignalementNom === filterType.value);
  }
  
  // Filtre par entreprise
  if (filterEntreprise.value) {
    filtered = filtered.filter(s => s.entrepriseNom === filterEntreprise.value);
  }
  
  // Filtre par date
  if (filterDate.value) {
    const now = new Date();
    filtered = filtered.filter(s => {
      const signalementDate = new Date(s.dateSignalement);
      
      switch (filterDate.value) {
        case 'recent':
          const today = new Date();
          today.setHours(0, 0, 0, 0);
          return signalementDate >= today;
        case 'week':
          const oneWeekAgo = new Date();
          oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
          return signalementDate >= oneWeekAgo;
        case 'month':
          const oneMonthAgo = new Date();
          oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);
          return signalementDate >= oneMonthAgo;
        default:
          return true;
      }
    });
  }
  
  // Trier par date (plus récent en premier)
  return filtered.sort((a, b) => new Date(b.dateSignalement).getTime() - new Date(a.dateSignalement).getTime());
});

// Options pour les filtres
const typeOptions = computed(() => {
  return allTypes.value.map(t => t.nom).filter(Boolean).sort();
});

const entrepriseOptions = computed(() => {
  return allEntreprises.value.map(e => e.nom).filter(Boolean).sort();
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

// Charger les données pour les filtres
const loadFilterData = async () => {
  try {
    isLoadingFilters.value = true;
    const [typesData, entreprisesData] = await Promise.all([
      typeSignalementService.getAll(),
      entrepriseService.getAll()
    ]);
    
    allTypes.value = typesData;
    allEntreprises.value = entreprisesData;
  } catch (error) {
    console.error('Erreur lors du chargement des données de filtres:', error);
    // Fallback vers les données des signalements existants
    const types = [...new Set(signalements.value.map(s => s.typeSignalementNom).filter(Boolean))];
    const entreprises = [...new Set(signalements.value.map(s => s.entrepriseNom).filter(Boolean))];
    
    allTypes.value = types.map((nom, index) => ({ id: index + 1, code: '', nom, icon: '' }));
    allEntreprises.value = entreprises.map((nom, index) => ({ id: index + 1, code: '', nom, logo: '' }));
  } finally {
    isLoadingFilters.value = false;
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

// Fonctions pour la sidebar de profil
const openProfilSidebar = () => {
  showProfilSidebar.value = true;
};

const closeProfilSidebar = () => {
  showProfilSidebar.value = false;
};

const handleLogout = () => {
  // La déconnexion est gérée dans la sidebar
  // Recharger les données après déconnexion
  signalements.value = [];
  currentUser.value = null;
};

// Fonctions pour les filtres
const clearFilters = () => {
  searchText.value = '';
  filterType.value = '';
  filterEntreprise.value = '';
  filterDate.value = '';
};

const removeFilter = (filterName: string) => {
  switch (filterName) {
    case 'type':
      filterType.value = '';
      break;
    case 'entreprise':
      filterEntreprise.value = '';
      break;
    case 'date':
      filterDate.value = '';
      break;
  }
};

const getDateFilterLabel = (value: string) => {
  switch (value) {
    case 'recent': return "Aujourd'hui";
    case 'week': return 'Cette semaine';
    case 'month': return 'Ce mois';
    default: return value;
  }
};

// Lifecycle
onMounted(() => {
  // Charger les données des filtres au démarrage
  loadFilterData();
  
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
  cursor: pointer;
  transition: transform 0.2s ease;
}

.profile-avatar:hover {
  transform: scale(1.05);
}

.profile-avatar:active {
  transform: scale(0.95);
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
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
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

/* Recherche et filtres */
.search-filter-section {
  margin-bottom: 20px;
  background: white;
  border-radius: 16px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

.search-container {
  margin-bottom: 16px;
}

.custom-searchbar {
  --background: #ffffff;
  --border-radius: 12px;
  --box-shadow: none;
  --color: #1a1a1a !important;
  --placeholder-color: #8e8e93 !important;
  --icon-color: #8e8e93;
  padding: 0;
  border: 2px solid #e9ecef;
  transition: all 0.2s ease;
}

.custom-searchbar:focus-within {
  border-color: #007aff;
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

.custom-searchbar input {
  color: #1a1a1a !important;
  font-weight: 500;
}

.custom-searchbar::part(icon) {
  color: #8e8e93 !important;
}

.filters-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.filter-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 8px;
}

.filter-item {
  display: flex;
  flex-direction: column;
}

.filter-select {
  --padding-start: 12px;
  --padding-end: 12px;
  --padding-top: 10px;
  --padding-bottom: 10px;
  --border-radius: 10px;
  --background: #ffffff;
  --color: #1a1a1a !important;
  --placeholder-color: #8e8e93 !important;
  --placeholder-opacity: 1;
  border: 2px solid #e9ecef;
  font-size: 14px;
  min-height: 40px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.filter-select:focus,
.filter-select.select-expanded {
  --background: #ffffff;
  border-color: #007aff;
  box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.1);
}

/* Force la couleur du texte dans les selects */
.filter-select::part(text) {
  color: #1a1a1a !important;
  font-weight: 500 !important;
}

.filter-select::part(placeholder) {
  color: #8e8e93 !important;
  opacity: 1 !important;
}

.filter-select::part(icon) {
  color: #8e8e93 !important;
}

/* Styles pour les options dans l'action sheet */
ion-select-option {
  --color: #1a1a1a !important;
  font-weight: 500;
}

ion-action-sheet {
  --color: #1a1a1a;
}

ion-action-sheet .action-sheet-button {
  color: #1a1a1a !important;
  font-weight: 500 !important;
}

.active-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 8px;
}

.filter-chip {
  --background: #007aff;
  --color: white;
  height: 32px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-chip:hover {
  --background: #0056cc;
}

.clear-filters-btn {
  background: #ff3b30;
  color: white;
  border: none;
  border-radius: 16px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.clear-filters-btn:hover {
  background: #e6342a;
  transform: scale(1.05);
}

.results-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.results-count {
  font-size: 13px;
  color: #8e8e93;
  font-weight: 500;
}

/* Aucun résultat */
.no-results {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.no-results-icon {
  width: 64px;
  height: 64px;
  background: rgba(142, 142, 147, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.no-results-icon-svg {
  width: 32px;
  height: 32px;
  color: #8e8e93;
}

.no-results-title {
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.no-results-description {
  font-size: 14px;
  color: #8e8e93;
  margin: 0 0 20px 0;
  line-height: 1.4;
}

.clear-filters-btn-alt {
  background: #007aff;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 12px 20px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.clear-filters-btn-alt:hover {
  background: #0056cc;
  transform: translateY(-1px);
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
  font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
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
