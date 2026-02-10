<!-- YAkXVM$47UqG5@b -->
<template>
  <div 
    class="detail-signalement-overlay" 
    :class="{ 'is-open': isOpen }"
    @click="handleOverlayClick"
  >
    <div 
      class="detail-signalement-sheet"
      :class="`position-${position}`"
      :style="{ transform: `translateY(${translateY}px)` }"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
    >
      <!-- Handle de drag -->
      <div class="drag-handle" ref="dragHandle">
        <div class="handle-bar"></div>
      </div>

      <!-- Header -->
      <div class="sheet-header" ref="sheetHeader">
        <div class="header-content">
          <div class="title-section">
            <div class="item-icon">
              <div 
                class="type-icon" 
                :style="{ color: getSignalementColor(signalement.typeSignalementNom) }"
                v-html="getTypeIcon(signalement.typeSignalementNom)"
                role="img"
              ></div>
            </div>
            <h2 class="sheet-title">{{ signalement.typeSignalementNom }}</h2>
          </div>
          
          <button class="close-button" @click="$emit('close')">
            <ion-icon :icon="closeOutline"></ion-icon>
          </button>
        </div>
      </div>

      <!-- Contenu -->
      <div class="sheet-content">
        <!-- Informations principales -->
        <div class="info-section">
          <div class="info-row" v-if="signalement.entrepriseNom">
            <div class="info-label">
              <ion-icon :icon="businessOutline" class="info-icon"></ion-icon>
              <span>Entreprise responsable</span>
            </div>
            <div class="info-value">{{ signalement.entrepriseNom }}</div>
          </div>

          <div class="info-row">
            <div class="info-label">
              <ion-icon :icon="locationOutline" class="info-icon"></ion-icon>
              <span>Position</span>
            </div>
            <div class="info-value">
              {{ Number(signalement.latitude).toFixed(6) }}, {{ Number(signalement.longitude).toFixed(6) }}
            </div>
          </div>

          <div class="info-row">
            <div class="info-label">
              <ion-icon :icon="timeOutline" class="info-icon"></ion-icon>
              <span>Date de signalement</span>
            </div>
            <div class="info-value">{{ formatDate(signalement.dateSignalement) }}</div>
          </div>

          <div class="info-row" v-if="signalement.utilisateurEmail">
            <div class="info-label">
              <ion-icon :icon="personOutline" class="info-icon"></ion-icon>
              <span>Signalé par</span>
            </div>
            <div class="info-value">{{ signalement.utilisateurEmail }}</div>
          </div>
        </div>

        <!-- Description -->
        <div class="description-section" v-if="signalement.description">
          <div class="section-title">
            <ion-icon :icon="documentTextOutline" class="section-icon"></ion-icon>
            <span>Description</span>
          </div>
          <p class="description-text">{{ signalement.description }}</p>
        </div>

        <!-- Détails techniques -->
        <div class="details-section" v-if="signalement.surface || signalement.budget">
          <div class="section-title">
            <ion-icon :icon="constructOutline" class="section-icon"></ion-icon>
            <span>Détails techniques</span>
          </div>
          
          <div class="detail-grid">
            <div class="detail-item" v-if="signalement.surface">
              <ion-icon :icon="squareOutline" class="detail-icon"></ion-icon>
              <div class="detail-content">
                <span class="detail-label">Surface affectée</span>
                <span class="detail-value">{{ signalement.surface }} m²</span>
              </div>
            </div>
            
            <div class="detail-item" v-if="signalement.budget">
              <ion-icon :icon="cardOutline" class="detail-icon"></ion-icon>
              <div class="detail-content">
                <span class="detail-label">Budget estimé</span>
                <span class="detail-value">{{ formatBudget(signalement.budget) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Section Photos -->
        <div class="photos-section" v-if="signalement.photos && signalement.photos.length > 0">
          <div class="section-title">
            <ion-icon :icon="imageOutline" class="section-icon"></ion-icon>
            <span>Photos</span>
          </div>
          <div class="photo-cover-container" @click="openGallery">
            <img :src="signalement.photos[0]" alt="Photo du signalement" class="photo-cover" />
            <div class="photo-overlay" v-if="signalement.photos.length > 1">
              <ion-icon :icon="imagesOutline" class="photo-overlay-icon"></ion-icon>
              <span class="photo-count">+{{ signalement.photos.length }} photos</span>
            </div>
            <div class="photo-overlay photo-overlay-single" v-else>
              <ion-icon :icon="expandOutline" class="photo-overlay-icon"></ion-icon>
              <span class="photo-count">Voir la photo</span>
            </div>
          </div>
        </div>

        <!-- Section Statut en bas -->
        <div class="status-section">
          <div class="section-title">
            <span>Statut</span>
          </div>
          <div 
            class="status-badge-large"
            :style="{ backgroundColor: getStatusConfig(signalement.status).color }"
          >
            <ion-icon :icon="getStatusIcon(signalement.status)" class="status-icon"></ion-icon>
            <span class="status-label">{{ getStatusConfig(signalement.status).label }}</span>
          </div>
          <div class="status-date">
            <ion-icon :icon="timeOutline" class="status-date-icon"></ion-icon>
            <span>Mis à jour le {{ formatDateStatus(signalement.dateStatus) }}</span>
          </div>
        </div>

      </div>
    </div>

    <!-- Modal Galerie Photos -->
    <div 
      class="gallery-overlay" 
      :class="{ 'is-open': isGalleryOpen }" 
      @click.self="closeGallery"
    >
      <div class="gallery-header">
        <span class="gallery-counter">{{ currentPhotoIndex + 1 }} / {{ signalement.photos?.length || 0 }}</span>
        <button class="gallery-close-btn" @click="closeGallery">
          <ion-icon :icon="closeOutline"></ion-icon>
        </button>
      </div>

      <div 
        class="gallery-swiper"
        @touchstart="handleGalleryTouchStart"
        @touchmove="handleGalleryTouchMove"
        @touchend="handleGalleryTouchEnd"
      >
        <div 
          class="gallery-track"
          :style="{ transform: `translateX(calc(-${currentPhotoIndex * 100}% + ${galleryDeltaX}px))` }"
        >
          <div 
            class="gallery-slide" 
            v-for="(photo, index) in signalement.photos" 
            :key="index"
          >
            <img :src="photo" :alt="`Photo ${index + 1}`" class="gallery-image" />
          </div>
        </div>
      </div>

      <div class="gallery-dots" v-if="(signalement.photos?.length || 0) > 1">
        <span 
          class="gallery-dot" 
          :class="{ active: index === currentPhotoIndex }" 
          v-for="(_, index) in signalement.photos" 
          :key="index"
          @click="currentPhotoIndex = index"
        ></span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { IonIcon } from '@ionic/vue';
import {
  closeOutline,
  chevronUpOutline,
  chevronDownOutline,
  businessOutline,
  locationOutline,
  timeOutline,
  personOutline,
  documentTextOutline,
  constructOutline,
  squareOutline,
  cardOutline,
  alertCircleOutline,
  warningOutline,
  hourglassOutline,
  checkmarkCircleOutline,
  checkmarkDoneOutline,
  closeCircleOutline,
  imageOutline,
  imagesOutline,
  expandOutline
} from 'ionicons/icons';
import type { Signalement } from '@/services/signalement/types';
import { SignalementStatus, SignalementStatusConfig } from '@/services/signalement/types';
import { TypeSignalementService } from '@/services/signalement/TypeSignalementService';

interface Props {
  signalement: Signalement;
  isOpen: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
}>();

// Instance du service
const typeSignalementService = new TypeSignalementService();

// Types de signalement avec leurs couleurs et icônes
const typesSignalement = ref<any[]>([]);

// État du bottom sheet
type SheetPosition = 'peek' | 'half' | 'expanded';
const position = ref<SheetPosition>('half');
const translateY = ref(0);

// État de la galerie photos
const isGalleryOpen = ref(false);
const currentPhotoIndex = ref(0);
const galleryDeltaX = ref(0);
let galleryStartX = 0;
let isGallerySwiping = false;

const openGallery = () => {
  currentPhotoIndex.value = 0;
  galleryDeltaX.value = 0;
  isGalleryOpen.value = true;
};

const closeGallery = () => {
  isGalleryOpen.value = false;
};

const handleGalleryTouchStart = (e: TouchEvent) => {
  galleryStartX = e.touches[0].clientX;
  isGallerySwiping = true;
  galleryDeltaX.value = 0;
};

const handleGalleryTouchMove = (e: TouchEvent) => {
  if (!isGallerySwiping) return;
  e.preventDefault();
  galleryDeltaX.value = e.touches[0].clientX - galleryStartX;
};

const handleGalleryTouchEnd = () => {
  if (!isGallerySwiping) return;
  isGallerySwiping = false;
  const totalPhotos = props.signalement.photos?.length || 0;
  const threshold = 60;

  if (galleryDeltaX.value < -threshold && currentPhotoIndex.value < totalPhotos - 1) {
    currentPhotoIndex.value++;
  } else if (galleryDeltaX.value > threshold && currentPhotoIndex.value > 0) {
    currentPhotoIndex.value--;
  }
  galleryDeltaX.value = 0;
};

// Refs pour le drag
const dragHandle = ref<HTMLElement>();
const sheetHeader = ref<HTMLElement>();

// Variables de drag
let startY = 0;
let startTranslateY = 0;
let isDragging = false;

// Constantes de positions
const SHEET_HEIGHT = {
  peek: window.innerHeight * 0.25,
  half: window.innerHeight * 0.6,
  expanded: window.innerHeight * 0.9
};

// Fonction pour obtenir la couleur d'un type de signalement
const getSignalementColor = (typeSignalementNom?: string): string => {
  if (!typeSignalementNom) return '#FF4444'; // Rouge par défaut
  
  // Chercher la couleur dans les types chargés depuis Firestore
  const typeData = typesSignalement.value.find(t => t.nom === typeSignalementNom);
  return typeData?.couleur || '#FF4444'; // Rouge si pas de couleur définie
};

// Fonction pour obtenir l'icône d'un type de signalement
const getSignalementIcon = (typeSignalementNom?: string): string => {
  if (!typeSignalementNom) return 'alert-circle-outline'; // Icône par défaut
  
  // Chercher l'icône dans les types chargés depuis Firestore
  const typeData = typesSignalement.value.find(t => t.nom === typeSignalementNom);
  return typeData?.icon || 'alert-circle-outline'; // Icône par défaut si pas d'icône définie
};

// Fonction pour créer l'HTML SVG d'une icône Ionic
const createIconSVG = (iconName: string): string => {
  // Map des icônes Ionic en SVG (version simplifiée)
  const iconSVGs: Record<string, string> = {
    'ellipse-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448,256c0-106-86-192-192-192S64,150,64,256s86,192,192,192S448,362,448,256Z"/></svg>',
    'remove-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="m368,368,0-224-224,0,0,224"/></svg>',
    'trending-down-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><polyline points="352,368 464,368 464,256" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="m48,144,169.37,169.37a32,32,0,0,0,45.26,0l50.74-50.74a32,32,0,0,1,45.26,0L448,352" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>',
    'water-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M400,320c0,88.37-55.63,144-144,144s-144-55.63-144-144c0-94.83,103.23-222.85,134.89-259.88a12,12,0,0,1,18.22,0C296.77,97.15,400,225.17,400,320Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/></svg>',
    'alert-circle-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M448,256c0-106-86-192-192-192S64,150,64,256s86,192,192,192S448,362,448,256Z" fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32"/><path d="m250.26,166.05,5.74,122,5.73-122a6,6,0,0,0-6-6.44h0A6,6,0,0,0,250.26,166.05Z" fill="currentColor"/><circle cx="256" cy="340" r="10" fill="currentColor"/></svg>',
    'swap-vertical-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><polyline points="464,208 352,96 240,208" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="352" y1="113.13" x2="352" y2="416" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><polyline points="48,304 160,416 272,304" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="160" y1="398.87" x2="160" y2="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>',
    'man-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><circle cx="256" cy="56" r="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="M304,128H208c-26.51,0-48,21.49-48,48V320c0,26.51,21.49,48,48,48h96c26.51,0,48-21.49,48-48V176C352,149.49,330.51,128,304,128Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="208" y1="368" x2="208" y2="464" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="304" y1="368" x2="304" y2="464" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>',
    'warning-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M85.57,446.25H426.43a32,32,0,0,0,28.17-47.17L284.18,82.58c-12.09-22.44-44.27-22.44-56.36,0L57.4,399.08A32,32,0,0,0,85.57,446.25Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="m250.26,195.39,5.74,122,5.73-122a6,6,0,0,0-6-6.44h0A6,6,0,0,0,250.26,195.39Z" fill="currentColor"/><circle cx="256" cy="397.25" r="10" fill="currentColor"/></svg>',
    'car-crash-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M32,192l64,32,112-64,112,64,64-32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><rect x="144" y="256" width="224" height="160" rx="16" ry="16" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="32"/><circle cx="336" cy="352" r="16" fill="currentColor"/><circle cx="176" cy="352" r="16" fill="currentColor"/><path d="m144,256-30-64H70.62a8,8,0,0,0-7.91,9.7L80,256" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path d="m368,256,30-64h43.38a8,8,0,0,1,7.91,9.7L432,256" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>',
    'trash-outline': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M112,112l20,320c.95,18.49,14.4,32,32,32H348c17.67,0,30.87-13.51,32-32l20-320" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><path stroke="currentColor" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" d="M80,112H432"/><path d="M192,112V72h0a23.93,23.93,0,0,1,24-24h80a23.93,23.93,0,0,1,24,24h0v40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="256" y1="176" x2="256" y2="400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="184" y1="176" x2="192" y2="400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/><line x1="328" y1="176" x2="320" y2="400" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32"/></svg>'
  };
  
  return iconSVGs[iconName] || iconSVGs['alert-circle-outline'];
};

// Icônes selon le type (version SVG)
const getTypeIcon = (typeName?: string) => {
  const iconName = getSignalementIcon(typeName);
  return createIconSVG(iconName);
};

// Configuration du statut
const getStatusConfig = (status?: SignalementStatus) => {
  if (!status || !SignalementStatusConfig[status]) {
    return SignalementStatusConfig[SignalementStatus.EN_ATTENTE];
  }
  return SignalementStatusConfig[status];
};

// Icône du statut
const getStatusIcon = (status?: SignalementStatus) => {
  const iconMap: Record<string, any> = {
    'hourglass-outline': hourglassOutline,
    'checkmark-circle-outline': checkmarkCircleOutline,
    'construct-outline': constructOutline,
    'checkmark-done-outline': checkmarkDoneOutline,
    'close-circle-outline': closeCircleOutline
  };
  const config = getStatusConfig(status);
  return iconMap[config.icon] || hourglassOutline;
};

// Formatage
const formatDate = (date: Date | string) => {
  const d = new Date(date);
  return d.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatDateStatus = (date: Date | string) => {
  const d = new Date(date);
  return d.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const formatBudget = (budget: number) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'MGA',
    currencyDisplay: 'code'
  }).format(budget);
};

// Gestion du clic sur l'overlay
const handleOverlayClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    emit('close');
  }
};

// Toggle expand/collapse
const toggleExpand = () => {
  position.value = position.value === 'peek' ? 'half' : 'peek';
  translateY.value = 0;
};

// Gestion du drag
const handleTouchStart = (event: TouchEvent) => {
  const touch = event.touches[0];
  const target = event.target as HTMLElement;
  
  // Seul le drag-handle et le header permettent de drag
  const canDrag = dragHandle.value?.contains(target) ||
                  sheetHeader.value?.contains(target);
  
  if (!canDrag) return;
  
  startY = touch.clientY;
  startTranslateY = translateY.value;
  isDragging = true;
};

const handleTouchMove = (event: TouchEvent) => {
  if (!isDragging) return;
  
  event.preventDefault();
  const touch = event.touches[0];
  const deltaY = touch.clientY - startY;
  translateY.value = Math.max(-100, startTranslateY + deltaY);
};

const handleTouchEnd = () => {
  if (!isDragging) return;
  
  isDragging = false;
  
  const threshold = 80;
  
  // Déterminer la nouvelle position selon la direction du drag
  if (translateY.value > threshold) {
    // Drag vers le bas
    if (position.value === 'expanded') {
      position.value = 'half';
    } else {
      // Depuis half ou peek, fermer
      emit('close');
      position.value = 'half'; // Reset pour la prochaine ouverture
      translateY.value = 0;
      return;
    }
  } else if (translateY.value < -threshold) {
    // Drag vers le haut
    if (position.value === 'peek') {
      position.value = 'half';
    } else if (position.value === 'half') {
      position.value = 'expanded';
    }
  }
  
  translateY.value = 0;
};

// Charger les types de signalement au montage du composant
onMounted(async () => {
  try {
    typesSignalement.value = await typeSignalementService.getAll();
  } catch (error) {
    console.error('Erreur lors du chargement des types de signalement:', error);
  }
});
</script>

<style scoped>
.detail-signalement-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4);
  z-index: 10000;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

.detail-signalement-overlay.is-open {
  opacity: 1;
  visibility: visible;
}

.detail-signalement-sheet {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 20px 20px 0 0;
  min-height: 25vh;
  max-height: 90vh;
  transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.detail-signalement-sheet.position-peek {
  transform: translateY(60vh);
}

.detail-signalement-sheet.position-half {
  transform: translateY(25vh);
}

.detail-signalement-sheet.position-expanded {
  transform: translateY(5vh);
}

.drag-handle {
  display: flex;
  justify-content: center;
  padding: 8px 0;
  cursor: grab;
}

.drag-handle:active {
  cursor: grabbing;
}

.handle-bar {
  width: 40px;
  height: 4px;
  background-color: rgba(0, 0, 0, 0.2);
  border-radius: 2px;
}

.sheet-header {
  padding: 0 20px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.title-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.item-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.type-icon {
  width: 24px;
  height: 24px;
  font-size: 24px;
}

.type-icon svg {
  width: 100%;
  height: 100%;
  color: currentColor;
}

.sheet-title {
  font-size: 20px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.close-button {
  background: rgba(0, 0, 0, 0.1);
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.close-button:hover {
  background: rgba(0, 0, 0, 0.2);
}

.close-button ion-icon {
  font-size: 18px;
  color: #666;
}

.toggle-section {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.toggle-section:hover {
  background: rgba(0, 122, 255, 0.1);
}

.expand-text {
  font-size: 14px;
  font-weight: 500;
  color: #007AFF;
}

.toggle-icon {
  font-size: 16px;
  color: #007AFF;
  transition: transform 0.2s;
}

.sheet-content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

.info-section {
  margin-bottom: 24px;
}

.info-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 16px;
}

.info-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #666;
}

.info-icon {
  font-size: 16px;
  color: #007AFF;
}

.info-value {
  font-size: 16px;
  color: #1a1a1a;
  margin-left: 24px;
  font-weight: 400;
}

.description-section,
.details-section {
  margin-bottom: 24px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
}

.section-icon {
  font-size: 18px;
  color: #007AFF;
}

.description-text {
  font-size: 14px;
  line-height: 1.5;
  color: #444;
  margin: 0;
  padding: 12px;
  background: rgba(0, 122, 255, 0.05);
  border-radius: 8px;
  border-left: 3px solid #007AFF;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 8px;
}

.detail-icon {
  font-size: 20px;
  color: #666;
}

.detail-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detail-label {
  font-size: 12px;
  color: #666;
  font-weight: 500;
}

.detail-value {
  font-size: 14px;
  color: #1a1a1a;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 480px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .sheet-content {
    padding: 16px;
  }
}

/* Section Statut */
.status-section {
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.status-badge-large {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 14px 24px;
  border-radius: 12px;
  color: #ffffff;
  font-size: 16px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.status-badge-large .status-icon {
  font-size: 20px;
  color: #ffffff;
}

.status-badge-large .status-label {
  line-height: 1;
}

.status-date {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  margin-top: 12px;
  font-size: 13px;
  color: #666;
}

.status-date-icon {
  font-size: 16px;
  color: #999;
}

/* Section Photos */
.photos-section {
  margin-bottom: 24px;
}

.photo-cover-container {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  aspect-ratio: 16 / 10;
  background: #e0e0e0;
}

.photo-cover {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.photo-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.65));
  color: #fff;
  font-weight: 600;
  font-size: 15px;
}

.photo-overlay-single {
  opacity: 0;
  transition: opacity 0.2s;
}

.photo-cover-container:active .photo-overlay-single {
  opacity: 1;
}

.photo-overlay-icon {
  font-size: 20px;
}

.photo-count {
  line-height: 1;
}

/* Galerie plein écran */
.gallery-overlay {
  position: fixed;
  inset: 0;
  z-index: 20000;
  background: rgba(0, 0, 0, 0.95);
  display: flex;
  flex-direction: column;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
}

.gallery-overlay.is-open {
  opacity: 1;
  visibility: visible;
}

.gallery-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  padding-top: max(16px, env(safe-area-inset-top));
}

.gallery-counter {
  color: #fff;
  font-size: 16px;
  font-weight: 600;
}

.gallery-close-btn {
  background: rgba(255, 255, 255, 0.15);
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #fff;
  font-size: 22px;
}

.gallery-swiper {
  flex: 1;
  overflow: hidden;
  display: flex;
  align-items: center;
}

.gallery-track {
  display: flex;
  width: 100%;
  height: 100%;
  transition: transform 0.3s ease;
}

.gallery-slide {
  flex: 0 0 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.gallery-image {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  border-radius: 4px;
  user-select: none;
  -webkit-user-drag: none;
}

.gallery-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  padding-bottom: max(16px, env(safe-area-inset-bottom));
}

.gallery-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.35);
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}

.gallery-dot.active {
  background: #fff;
  transform: scale(1.3);
}
</style>