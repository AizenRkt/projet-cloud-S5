<template>
  <ion-page>
    <ion-content :fullscreen="true">
      <!-- Barre de recherche -->
      <div class="search-container" 
           :class="{ 
             'search-active': isSearchActive,
             'sheet-open': showSignalementForm || showSignalementDetail 
           }">
        <div class="search-bar">
          <div class="search-input-wrapper">
            <ion-icon :icon="searchOutline" class="search-icon"></ion-icon>
            <input 
              v-model="searchQuery"
              @input="handleSearchInput"
              @focus="showSearchResults = true; isSearchActive = true"
              @blur="handleSearchBlur"
              placeholder="Rechercher un endroit..."
              class="search-input"
              type="text"
            />
            <button 
              v-if="searchQuery" 
              @click="clearSearch" 
              class="clear-button"
            >
              <ion-icon :icon="closeOutline"></ion-icon>
            </button>
          </div>
        </div>
        
        <!-- Résultats de recherche -->
        <div v-if="showSearchResults && (searchResults.length > 0 || isSearching) && !showSignalementForm && !showSignalementDetail" class="search-results">
          <div v-if="isSearching" class="search-loading">
            <ion-icon :icon="refreshOutline" class="loading-icon"></ion-icon>
            <span>Recherche en cours...</span>
          </div>
          
          <div v-else-if="searchResults.length > 0" class="results-list">
            <div 
              v-for="result in searchResults" 
              :key="result.id"
              @click="selectLocation(result)"
              class="result-item"
            >
              <div class="result-icon">
                <ion-icon :icon="locationOutline"></ion-icon>
              </div>
              <div class="result-content">
                <div class="result-name">{{ result.name }}</div>
                <div class="result-address">{{ result.displayName }}</div>
              </div>
            </div>
          </div>
          
          <div v-else-if="searchQuery && !isSearching" class="no-results">
            <ion-icon :icon="alertCircleOutline"></ion-icon>
            <span>Aucun résultat trouvé</span>
          </div>
        </div>
        
        <!-- Suggestions populaires -->
        <div v-if="showSearchResults && !searchQuery && !isSearching && !showSignalementForm && !showSignalementDetail" class="search-suggestions">
          <div class="suggestions-title">Recherches populaires</div>
          <div class="suggestions-grid">
            <button 
              v-for="suggestion in popularSuggestions.slice(0, 6)" 
              :key="suggestion"
              @click="searchQuery = suggestion; handleSearchInput()"
              class="suggestion-chip"
            >
              {{ suggestion }}
            </button>
          </div>
        </div>
      </div>

      <!-- Filtres de signalements -->
      <div class="filters-container" 
           v-if="!isSearchActive && !showSignalementForm && !showSignalementDetail"
           :class="{ 'filters-hidden': showSignalementForm || showSignalementDetail }">
        <div class="filters-chips">
          <button 
            class="filter-chip"
            :class="{ 'active': currentFilter === 'all' }"
            @click="setFilter('all')"
          >
            <ion-icon :icon="globeOutline" class="chip-icon"></ion-icon>
            <span>Tous les signalements</span>
            <span class="chip-count" v-if="allSignalementsCount > 0">({{ allSignalementsCount }})</span>
          </button>
          
          <button 
            class="filter-chip"
            :class="{ 'active': currentFilter === 'mine' }"
            @click="setFilter('mine')"
          >
            <ion-icon :icon="personOutline" class="chip-icon"></ion-icon>
            <span>Mes signalements</span>
            <span class="chip-count" v-if="mySignalementsCount > 0">({{ mySignalementsCount }})</span>
          </button>
        </div>
      </div>

      <!-- Carte -->
      <div id="map"></div>

      <!-- Bouton flottant pour recentrer -->
      <ion-fab vertical="bottom" horizontal="end" slot="fixed">
        <ion-fab-button class="location-button" @click="goToMyLocation">
          <ion-icon :icon="locateOutline"></ion-icon>
        </ion-fab-button>
      </ion-fab>

      <!-- Formulaire de signalement en bottom sheet -->
      <SignalementForm 
        v-if="formPosition"
        :latitude="formPosition[0]" 
        :longitude="formPosition[1]"
        :is-open="showSignalementForm"
        @close="closeForm"
        @submit="handleSubmitSignalement"
      />

      <!-- Détails du signalement en bottom sheet -->
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
import { onBeforeUnmount, ref } from "vue";
import { useRouter } from "vue-router";
import * as L from "leaflet";
import GeolocalisationService from "@/services/geolocalisation";
import GeoSearchService, { type SearchLocation } from "@/services/geosearch";
import { signalementService } from "@/services/signalement";
import { onIonViewDidEnter } from "@ionic/vue";

// **Vrai import corrigé**
import SignalementForm from "@/components/signalement/FormulaireSignalement.vue";
import TooltipSignalement from "@/components/signalement/TooltipSignalement.vue";
import DetailSignalement from "@/components/signalement/DetailSignalement.vue";

import type { Signalement } from "@/services/signalement/types";

/* Ionic imports */
import {
  IonPage,
  IonContent,
  IonFab,
  IonFabButton,
  IonIcon
} from "@ionic/vue";

import { 
  locateOutline, 
  searchOutline, 
  closeOutline, 
  locationOutline, 
  refreshOutline, 
  alertCircleOutline,
  globeOutline,
  personOutline
} from "ionicons/icons";

import { auth } from '@/firebase';
import { onAuthStateChanged, type User } from 'firebase/auth';

const router = useRouter();

/* Fix icônes Leaflet */
import { Icon } from "leaflet";
delete (Icon.Default.prototype as any)._getIconUrl;
Icon.Default.mergeOptions({
  iconRetinaUrl: "assets/marker-icon-2x.png",
  iconUrl: "assets/marker-icon.png",
  shadowUrl: "assets/marker-shadow.png"
});

let map: L.Map | null = null;
let userMarker: L.CircleMarker | null = null;
let signalementMarker: L.CircleMarker | null = null;
let signalementMarkers: L.CircleMarker[] = [];
let tooltipPopup: L.Popup | null = null;

// État pour le formulaire de signalement
const showSignalementForm = ref(false);
const formPosition = ref<[number, number] | null>(null);

// État pour les signalements existants
const signalements = ref<Signalement[]>([]);
const selectedSignalement = ref<Signalement | null>(null);
const showSignalementDetail = ref(false);

// État pour la recherche géographique
const searchQuery = ref('');
const searchResults = ref<SearchLocation[]>([]);
const showSearchResults = ref(false);
const isSearching = ref(false);
const isSearchActive = ref(false);
const popularSuggestions = ref(GeoSearchService.getPopularSuggestions());
let searchTimeout: NodeJS.Timeout | null = null;

// État pour les filtres de signalements
const currentFilter = ref<'all' | 'mine'>('all');
const currentUser = ref<User | null>(null);
const allSignalementsCount = ref(0);
const mySignalementsCount = ref(0);
const allSignalements = ref<Signalement[]>([]);
const mySignalements = ref<Signalement[]>([]);

const initMap = async () => {
  if (map) {
    map.invalidateSize();
    return;
  }
  map = L.map("map", { zoomControl: false }).setView([-18.8792, 47.5079], 13);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors"
  }).addTo(map);

  // Marqueur Antananarivo
  L.marker([-18.8792, 47.5079])
    .addTo(map);

  // Essayer de récupérer la position actuelle
  const pos = await GeolocalisationService.getCurrentPosition();
  if (pos) {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;
    map.setView([lat, lng], 15);

    userMarker = L.circleMarker([lat, lng], {
      radius: 8,
      fillColor: '#4285F4',
      color: '#ffffff',
      weight: 3,
      opacity: 1,
      fillOpacity: 1
    })
      .addTo(map)
      .bindPopup("Vous êtes ici")
      .openPopup();
  } else {
    console.warn("Permission localisation non accordée ou position indisponible");
  }

  // Charger les signalements existants
  await loadSignalements();

  // Clic sur la carte pour afficher le formulaire
  map.on("click", (e: L.LeafletMouseEvent) => {
    // Fermer les détails si ouverts
    if (showSignalementDetail.value) {
      showSignalementDetail.value = false;
      selectedSignalement.value = null;
    }

    // Supprimer le marqueur précédent s'il existe
    if (signalementMarker) {
      map!.removeLayer(signalementMarker);
    }
    
    // Créer un nouveau marqueur rouge à la position cliquée
    signalementMarker = L.circleMarker([e.latlng.lat, e.latlng.lng], {
      radius: 10,
      fillColor: '#FF4444',
      color: '#ffffff',
      weight: 2,
      opacity: 1,
      fillOpacity: 0.8
    }).addTo(map!);
    
    formPosition.value = [e.latlng.lat, e.latlng.lng];
    showSignalementForm.value = true;
  });
};

// Attendre que la vue Ionic soit réellement affichée pour éviter un conteneur à 0px
onIonViewDidEnter(() => {
  // Écouter les changements d'authentification
  onAuthStateChanged(auth, (user) => {
    currentUser.value = user;
    if (user) {
      loadMySignalements();
    }
  });
  
  // Petit délai pour laisser le layout se stabiliser avant Leaflet.invalidateSize()
  setTimeout(() => initMap(), 50);
});

onBeforeUnmount(() => {
  // Nettoyer tous les markers
  signalementMarkers.forEach(marker => {
    if (map && map.hasLayer(marker)) {
      map.removeLayer(marker);
    }
  });
  
  if (tooltipPopup && map) {
    map.closePopup(tooltipPopup);
  }

  map?.remove();
  map = null;
  userMarker = null;
  signalementMarker = null;
  signalementMarkers = [];
  tooltipPopup = null;
});

// Fonction pour recentrer sur la position actuelle
const goToMyLocation = async () => {
  if (!map) {
    await initMap();
    if (!map) return;
  }

  const pos = await GeolocalisationService.getCurrentPosition();
  if (!pos) {
    alert("Autorisez la localisation pour recentrer la carte");
    return;
  }

  const lat = pos.coords.latitude;
  const lng = pos.coords.longitude;
  map.setView([lat, lng], 15);

  if (userMarker) {
    userMarker.setLatLng([lat, lng]).update();
  } else {
    userMarker = L.circleMarker([lat, lng], {
      radius: 8,
      fillColor: '#4285F4',
      color: '#ffffff',
      weight: 3,
      opacity: 1,
      fillOpacity: 1
    })
      .addTo(map)
      .bindPopup("Vous êtes ici")
      .openPopup();
  }
};

// Fermer le formulaire
const closeForm = () => {
  showSignalementForm.value = false;
  formPosition.value = null;
  
  // Supprimer le marqueur de signalement
  if (signalementMarker && map) {
    map.removeLayer(signalementMarker);
    signalementMarker = null;
  }
};

// Charger tous les signalements existants
const loadSignalements = async () => {
  try {
    const data = await signalementService.getAll();
    allSignalements.value = data;
    allSignalementsCount.value = data.length;
    
    // Appliquer le filtre actuel
    applyCurrentFilter();
  } catch (error) {
    console.error("Erreur lors du chargement des signalements:", error);
  }
};

// Charger mes signalements
const loadMySignalements = async () => {
  if (!currentUser.value?.uid) {
    mySignalements.value = [];
    mySignalementsCount.value = 0;
    return;
  }
  
  try {
    const data = await signalementService.getByUser(currentUser.value.uid);
    mySignalements.value = data;
    mySignalementsCount.value = data.length;
    
    // Si le filtre est sur "mes signalements", actualiser l'affichage
    if (currentFilter.value === 'mine') {
      signalements.value = data;
      displaySignalementsOnMap();
    }
  } catch (error) {
    console.error("Erreur lors du chargement de mes signalements:", error);
  }
};

// Définir le filtre actuel
const setFilter = (filter: 'all' | 'mine') => {
  currentFilter.value = filter;
  applyCurrentFilter();
};

// Appliquer le filtre actuel
const applyCurrentFilter = () => {
  if (currentFilter.value === 'all') {
    signalements.value = allSignalements.value;
  } else {
    signalements.value = mySignalements.value;
  }
  displaySignalementsOnMap();
};

// Afficher les signalements sur la carte
const displaySignalementsOnMap = () => {
  if (!map) return;

  // Supprimer les anciens markers
  signalementMarkers.forEach(marker => map!.removeLayer(marker));
  signalementMarkers = [];

  // Ajouter les nouveaux markers
  signalements.value.forEach(signalement => {
    const marker = L.circleMarker([signalement.latitude, signalement.longitude], {
      radius: 8,
      fillColor: '#FF4444',
      color: '#ffffff',
      weight: 2,
      opacity: 1,
      fillOpacity: 0.8
    }).addTo(map!);

    // Tooltip au survol
    marker.on('mouseover', (e) => {
      if (tooltipPopup) {
        map!.closePopup(tooltipPopup);
      }
      
      // Créer le contenu du tooltip avec Vue
      const tooltipDiv = document.createElement('div');
      const tooltipComponent = document.createElement('tooltip-signalement');
      tooltipComponent.setAttribute(':signalement', JSON.stringify(signalement));
      tooltipDiv.appendChild(tooltipComponent);
      
      tooltipPopup = L.popup({
        closeButton: false,
        autoClose: false,
        closeOnEscapeKey: false,
        className: 'signalement-tooltip'
      })
      .setLatLng(e.latlng)
      .setContent(`
        <div class="tooltip-signalement">
          <div class="tooltip-header">
            <span class="type-name">${signalement.typeSignalementNom || 'Signalement'}</span>
          </div>
          <div class="tooltip-content">
            ${signalement.entrepriseNom ? `<div class="entreprise-info">${signalement.entrepriseNom}</div>` : ''}
            <div class="date-info">${new Date(signalement.dateSignalement).toLocaleDateString('fr-FR')}</div>
          </div>
          <div class="tooltip-footer">
            <span class="tap-hint">Appuyez pour plus de détails</span>
          </div>
        </div>
      `)
      .openOn(map!);
    });

    marker.on('mouseout', () => {
      if (tooltipPopup) {
        map!.closePopup(tooltipPopup);
        tooltipPopup = null;
      }
    });

    // Clic pour afficher les détails
    marker.on('click', (e) => {
      L.DomEvent.stopPropagation(e);
      if (tooltipPopup) {
        map!.closePopup(tooltipPopup);
        tooltipPopup = null;
      }
      selectedSignalement.value = signalement;
      showSignalementDetail.value = true;
    });

    signalementMarkers.push(marker);
  });
};

// Gérer la soumission du signalement
const handleSubmitSignalement = async (data: any) => {
  try {
    console.log("Données reçues du formulaire:", data);
    
    // Préparer les données pour Firebase
    const signalementData = {
      typeSignalementId: data.typeSignalementId,
      typeSignalementNom: data.typeSignalementNom,
      entrepriseId: data.entrepriseId,
      entrepriseNom: data.entrepriseNom,
      latitude: formPosition.value![0],
      longitude: formPosition.value![1],
      description: data.description,
      surface: data.surface,
      budget: data.budget,
      utilisateurId: data.utilisateurId, 
      utilisateurEmail: data.utilisateurEmail, 
      dateSignalement: new Date()
    };

    console.log("Données envoyées à Firebase:", signalementData);

    // Envoyer vers Firebase
    const signalementId = await signalementService.create(signalementData);
    
    console.log("Signalement créé avec ID:", signalementId);
    alert("Signalement enregistré avec succès !");
    
    // Recharger les signalements pour afficher le nouveau
    await loadSignalements();
    
    // Si on est connecté, recharger aussi mes signalements
    if (currentUser.value) {
      await loadMySignalements();
    }
    
    // Fermer le formulaire et supprimer le marqueur
    closeForm();
  } catch (error) {
    console.error("Erreur lors de l'enregistrement:", error);
    alert("Erreur lors de l'enregistrement du signalement");
  }
};

// Fermer les détails du signalement
const closeSignalementDetail = () => {
  showSignalementDetail.value = false;
  selectedSignalement.value = null;
};

// Fonctions de recherche géographique
const handleSearchInput = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
  
  searchTimeout = setTimeout(async () => {
    if (!searchQuery.value.trim()) {
      searchResults.value = [];
      return;
    }
    
    isSearching.value = true;
    try {
      const results = await GeoSearchService.searchInMadagascar(searchQuery.value);
      searchResults.value = results;
    } catch (error) {
      console.error('Erreur de recherche:', error);
      searchResults.value = [];
    } finally {
      isSearching.value = false;
    }
  }, 500);
};

const handleSearchBlur = () => {
  // Délai pour permettre le clic sur les résultats
  setTimeout(() => {
    showSearchResults.value = false;
    isSearchActive.value = false;
  }, 150);
};

const clearSearch = () => {
  searchQuery.value = '';
  searchResults.value = [];
  showSearchResults.value = false;
  isSearchActive.value = false;
};

const selectLocation = async (location: SearchLocation) => {
  if (!map) return;
  
  // Fermer la recherche
  showSearchResults.value = false;
  isSearchActive.value = false;
  searchQuery.value = location.name;
  
  // Centrer la carte sur l'endroit
  map.setView([location.latitude, location.longitude], 15);
  
  // Déplacer/créer le marqueur utilisateur
  if (userMarker) {
    userMarker.setLatLng([location.latitude, location.longitude]).update();
  } else {
    userMarker = L.circleMarker([location.latitude, location.longitude], {
      radius: 8,
      fillColor: '#4285F4',
      color: '#ffffff',
      weight: 3,
      opacity: 1,
      fillOpacity: 1
    })
      .addTo(map)
      .bindPopup(`Vous êtes à: ${location.name}`)
      .openPopup();
  }
  
  // Optionnel: obtenir l'adresse précise
  try {
    const address = await GeoSearchService.reverseGeocode(location.latitude, location.longitude);
    if (address && userMarker) {
      userMarker.bindPopup(`📍 ${location.name}<br><small>${address}</small>`).openPopup();
    }
  } catch (error) {
    console.warn('Impossible d\'obtenir l\'adresse détaillée:', error);
  }
};
</script>

<style scoped>
/* Location button styling */
.location-button {
  --background: rgba(74, 144, 226, 0.9);
  --background-activated: rgba(74, 144, 226, 1);
  --color: #ffffff;
  --box-shadow: 0 8px 24px rgba(74, 144, 226, 0.3);
  --border-radius: 16px;
  width: 56px;
  height: 56px;
  margin: 16px;
}

.location-button ion-icon {
  font-size: 24px;
}

ion-content {
  --padding-top: 0;
  --padding-bottom: 0;
  --background: #0f1115;
}

#map {
  /* Plein écran moins le tabbar en bas (≈ 56px) */
  height: calc(100vh - 56px);
  min-height: 400px;
  width: 100%;
}

/* Dark theme adjustments for consistency */
ion-page {
  --ion-background-color: #0f1115;
  --ion-text-color: #ffffff;
}

/* Styles pour les tooltips de signalement */
:global(.leaflet-popup.signalement-tooltip) {
  .leaflet-popup-content-wrapper {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 0;
  }
  
  .leaflet-popup-content {
    margin: 0;
    font-size: 14px;
  }
  
  .leaflet-popup-tip {
    background: rgba(255, 255, 255, 0.95);
    border: none;
  }
}

.tooltip-signalement {
  padding: 12px;
  min-width: 200px;
  max-width: 280px;
  color: #1a1a1a;
}

.tooltip-header {
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.type-name {
  font-weight: 600;
  color: #1a1a1a;
}

.tooltip-content {
  margin-bottom: 8px;
}

.entreprise-info,
.date-info {
  font-size: 12px;
  color: #666;
  margin-bottom: 4px;
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

/* Styles pour la barre de recherche */
.search-container {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 12px 16px;
  background: transparent;
  transition: all 0.3s ease;
}

/* Effet de flou quand la recherche est active */
.search-container.search-active {
  background: rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
}

/* Quand un bottom sheet est ouvert, réduire la taille et ajuster la position */
.search-container.sheet-open {
  transform: scale(0.95);
  opacity: 0.8;
}

.search-container.sheet-open .search-input-wrapper {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
}

/* Filtres de signalements */
.filters-container {
  position: absolute;
  top: 80px;
  left: 0;
  right: 0;
  z-index: 999;
  padding: 0 16px 12px;
  transition: all 0.3s ease;
}

.filters-container.filters-hidden {
  opacity: 0;
  transform: translateY(-20px);
  pointer-events: none;
}

.filters-chips {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 4px;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.filters-chips::-webkit-scrollbar {
  display: none;
}

.filter-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 24px;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  min-height: 44px;
}

.filter-chip:hover {
  background: rgba(255, 255, 255, 1);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.filter-chip.active {
  background: #007AFF;
  color: white;
  border-color: #007AFF;
  box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
}

.filter-chip.active:hover {
  background: #0056CC;
  border-color: #0056CC;
}

.chip-icon {
  font-size: 18px;
  flex-shrink: 0;
}

.filter-chip.active .chip-icon {
  color: white;
}

.chip-count {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 12px;
  padding: 2px 8px;
  font-size: 12px;
  font-weight: 600;
  margin-left: 4px;
}

.filter-chip.active .chip-count {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.search-bar {
  margin-bottom: 8px;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  overflow: hidden;
}

.search-icon {
  color: #666;
  font-size: 20px;
  margin-left: 16px;
  margin-right: 12px;
  flex-shrink: 0;
}

.search-input {
  flex: 1;
  padding: 16px 8px;
  border: none;
  background: transparent;
  font-size: 16px;
  color: #1a1a1a;
  outline: none;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
}

.search-input::placeholder {
  color: #999;
}

.clear-button {
  background: rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.clear-button:hover {
  background: rgba(0, 0, 0, 0.15);
}

.clear-button ion-icon {
  font-size: 18px;
  color: #666;
}

/* Résultats de recherche */
.search-results {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  max-height: 400px;
  overflow-y: auto;
}

.search-loading {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  color: #666;
  justify-content: center;
}

.loading-icon {
  font-size: 20px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.results-list {
  max-height: 300px;
  overflow-y: auto;
}

.result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.result-item:hover {
  background: rgba(0, 122, 255, 0.1);
}

.result-item:last-child {
  border-bottom: none;
}

.result-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(0, 122, 255, 0.1);
  border-radius: 8px;
  flex-shrink: 0;
}

.result-icon ion-icon {
  font-size: 18px;
  color: #007AFF;
}

.result-content {
  flex: 1;
  min-width: 0;
}

.result-name {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.result-address {
  font-size: 14px;
  color: #666;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.no-results {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  color: #666;
  justify-content: center;
}

.no-results ion-icon {
  font-size: 20px;
}

/* Suggestions populaires */
.search-suggestions {
  padding: 20px;
}

.suggestions-title {
  font-size: 14px;
  font-weight: 600;
  color: #666;
  margin-bottom: 16px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.suggestions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 8px;
}

.suggestion-chip {
  background: rgba(0, 122, 255, 0.1);
  border: 1px solid rgba(0, 122, 255, 0.2);
  border-radius: 20px;
  padding: 8px 16px;
  font-size: 14px;
  color: #007AFF;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
  font-weight: 500;
}

.suggestion-chip:hover {
  background: rgba(0, 122, 255, 0.2);
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 480px) {
  .search-container {
    padding: 8px 12px;
  }
  
  .suggestions-grid {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }
  
  .result-item {
    padding: 12px 16px;
  }
}

/* Animation d'apparition */
.search-results {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>