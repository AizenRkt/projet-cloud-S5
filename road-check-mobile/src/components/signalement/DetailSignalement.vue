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
            <ion-icon :icon="getTypeIcon(signalement.typeSignalementNom)" class="type-icon"></ion-icon>
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
import { ref, computed } from 'vue';
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

interface Props {
  signalement: Signalement;
  isOpen: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
}>();

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

// Icônes selon le type
const getTypeIcon = (typeName?: string) => {
  if (!typeName) return alertCircleOutline;
  
  const type = typeName.toLowerCase();
  if (type.includes('nid') || type.includes('trou')) return warningOutline;
  if (type.includes('route') || type.includes('chaussée')) return constructOutline;
  return alertCircleOutline;
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

.type-icon {
  font-size: 24px;
  color: #FF4444;
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