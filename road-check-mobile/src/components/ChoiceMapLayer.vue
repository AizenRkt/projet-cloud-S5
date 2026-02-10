<template>
  <div v-if="isOpen" class="layer-choice-overlay" @click="handleOverlayClick">
    <div class="layer-choice-sheet" :class="sheetStateClass" :style="sheetStyle" ref="sheetRef">
      <!-- Handle de glissement -->
      <div 
        class="sheet-handle" 
        ref="handleRef"
        @touchstart="onTouchStart"
        @touchmove="onTouchMove"
        @touchend="onTouchEnd"
        @mousedown="onMouseStart"
      >
        <div class="handle-bar"></div>
      </div>

      <!-- Contenu du sheet -->
      <div class="sheet-content">
        <!-- En-tête -->
        <div class="sheet-header">
          <h2 class="sheet-title">
            <ion-icon :icon="optionsOutline" class="title-icon"></ion-icon>
            Filtres et couches
          </h2>
          <p class="sheet-subtitle">Personnalisez l'affichage de votre carte</p>
        </div>

        <!-- Types de signalement -->
        <div class="filter-section">
          <h3 class="section-title">
            <ion-icon :icon="alertCircleOutline" class="section-icon"></ion-icon>
            Types de signalement
          </h3>
          <div class="filter-chips">
            <button 
              class="filter-chip"
              :class="{ 'active': selectedTypes.length === 0 }"
              @click="toggleTypeFilter(null)"
            >
              Tous les types
            </button>
            <button 
              v-for="type in signalementTypes" 
              :key="type.id"
              class="filter-chip"
              :class="{ 'active': selectedTypes.includes(type.id) }"
              @click="toggleTypeFilter(type.id)"
            >
              {{ type.nom }}
            </button>
          </div>
        </div>

        <!-- Statuts des signalements -->
        <div class="filter-section">
          <h3 class="section-title">
            <ion-icon :icon="checkboxOutline" class="section-icon"></ion-icon>
            Statut des signalements
          </h3>
          <div class="filter-chips">
            <button 
              class="filter-chip"
              :class="{ 'active': selectedStatuses.length === 0 }"
              @click="toggleStatusFilter(null)"
            >
              Tous les statuts
            </button>
            <button 
              v-for="status in signalementStatuses" 
              :key="status.key"
              class="filter-chip"
              :class="{ 'active': selectedStatuses.includes(status.key) }"
              @click="toggleStatusFilter(status.key)"
            >
              <ion-icon v-if="status.icon" :icon="status.icon" class="chip-icon"></ion-icon>
              {{ status.name }}
            </button>
          </div>
        </div>

        <!-- Couches de la carte -->
        <div class="filter-section">
          <h3 class="section-title">
            <ion-icon :icon="layersOutline" class="section-icon"></ion-icon>
            Style de carte
          </h3>
          <div class="layer-options">
            <div 
              v-for="layer in layers" 
              :key="layer.key"
              class="layer-option"
              :class="{ 'active': currentLayer === layer.key }"
              @click="selectLayer(layer.key)"
            >
              <div class="layer-preview">
                <ion-icon :icon="layer.icon" class="layer-icon"></ion-icon>
              </div>
              <div class="layer-info">
                <h3 class="layer-name">{{ layer.name }}</h3>
                <p class="layer-description">{{ layer.description }}</p>
              </div>
              <div class="layer-check" v-if="currentLayer === layer.key">
                <ion-icon :icon="checkmarkCircleOutline" class="check-icon"></ion-icon>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="sheet-actions">
          <button class="reset-btn" @click="resetFilters">
            <ion-icon :icon="refreshOutline"></ion-icon>
            Réinitialiser
          </button>
          <button class="close-btn" @click="handleClose">
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { IonIcon } from '@ionic/vue';
import { 
  layersOutline, 
  mapOutline, 
  imageOutline, 
  earthOutline, 
  checkmarkCircleOutline,
  optionsOutline,
  alertCircleOutline,
  checkboxOutline,
  refreshOutline,
  hourglassOutline,
  checkmarkCircle,
  constructOutline,
  checkmarkDoneOutline,
  closeCircleOutline
} from 'ionicons/icons';
import { SignalementStatus, SignalementStatusConfig } from '@/services/signalement/types';

interface Props {
  isOpen?: boolean;
  currentLayer: 'default' | 'satellite' | 'terrain';
  signalementTypes?: Array<{ id: number; nom: string; icon?: string }>;
  selectedTypes?: number[];
  selectedStatuses?: string[];
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  signalementTypes: () => [],
  selectedTypes: () => [],
  selectedStatuses: () => []
});

const emit = defineEmits<{
  close: [];
  layerChange: [layer: 'default' | 'satellite' | 'terrain'];
  typeFilterChange: [types: number[]];
  statusFilterChange: [statuses: string[]];
  resetFilters: [];
}>();

// États du bottom sheet
type SheetState = 'peek' | 'expanded';
const currentState = ref<SheetState>('expanded'); // Commencer en mode étendu vu le contenu plus complexe
const sheetRef = ref<HTMLElement | null>(null);
const handleRef = ref<HTMLElement | null>(null);
const isDragging = ref(false);
const dragStartY = ref(0);
const dragStartTime = ref(0);
const currentTranslateY = ref(0);

// États locaux pour les filtres
const selectedTypes = ref<number[]>([...props.selectedTypes]);
const selectedStatuses = ref<string[]>([...props.selectedStatuses]);

// Hauteurs pour chaque état (en %)
const SHEET_HEIGHTS = {
  peek: 50,      // Aperçu initial (plus grand à cause du contenu)
  expanded: 85   // Étendu
};

// Seuils pour le changement d'état
const VELOCITY_THRESHOLD = 0.3;
const DISTANCE_THRESHOLD = 50;

// Style du sheet calculé
const sheetStyle = computed(() => {
  if (isDragging.value) {
    return {
      transform: `translateY(${currentTranslateY.value}px)`,
      transition: 'none'
    };
  }
  
  const height = SHEET_HEIGHTS[currentState.value];
  return {
    height: `${height}vh`,
    transform: 'translateY(0)',
    transition: 'all 0.4s cubic-bezier(0.32, 0.72, 0, 1)'
  };
});

const sheetStateClass = computed(() => `state-${currentState.value}`);

// Options de couches
const layers = [
  {
    key: 'default' as const,
    name: 'Carte standard',
    description: 'Vue routière classique avec routes et noms',
    icon: mapOutline
  },
  {
    key: 'satellite' as const,
    name: 'Vue satellite',
    description: 'Images aériennes haute résolution',
    icon: imageOutline
  },
  {
    key: 'terrain' as const,
    name: 'Relief et topographie',
    description: 'Carte topographique avec élévation',
    icon: earthOutline
  }
];

// Options de statuts des signalements
const statusIconMap: Record<string, any> = {
  'hourglass-outline': hourglassOutline,
  'checkmark-circle-outline': checkmarkCircle,
  'construct-outline': constructOutline,
  'checkmark-done-outline': checkmarkDoneOutline,
  'close-circle-outline': closeCircleOutline
};

const signalementStatuses = Object.entries(SignalementStatusConfig).map(([key, config]) => ({
  key: key as SignalementStatus,
  name: config.label,
  icon: statusIconMap[config.icon] || null
}));

// Gestion des événements tactiles
const onTouchStart = (e: TouchEvent) => {
  startDrag(e.touches[0].clientY);
};

const onTouchMove = (e: TouchEvent) => {
  if (isDragging.value) {
    e.preventDefault();
    updateDrag(e.touches[0].clientY);
  }
};

const onTouchEnd = (e: TouchEvent) => {
  endDrag();
};

// Gestion des événements souris
const onMouseStart = (e: MouseEvent) => {
  startDrag(e.clientY);
  
  const onMouseMove = (e: MouseEvent) => {
    if (isDragging.value) {
      e.preventDefault();
      updateDrag(e.clientY);
    }
  };
  
  const onMouseUp = () => {
    endDrag();
    document.removeEventListener('mousemove', onMouseMove);
    document.removeEventListener('mouseup', onMouseUp);
  };
  
  document.addEventListener('mousemove', onMouseMove);
  document.addEventListener('mouseup', onMouseUp);
};

const startDrag = (clientY: number) => {
  isDragging.value = true;
  dragStartY.value = clientY;
  dragStartTime.value = Date.now();
  currentTranslateY.value = 0;
};

const updateDrag = (clientY: number) => {
  const deltaY = clientY - dragStartY.value;
  
  // Limiter le glissement vers le haut
  if (deltaY < 0 && currentState.value === 'expanded') {
    currentTranslateY.value = Math.max(deltaY * 0.3, -50);
  }
  // Permettre le glissement vers le bas
  else if (deltaY > 0) {
    currentTranslateY.value = deltaY;
  }
};

const endDrag = () => {
  if (!isDragging.value) return;
  
  const deltaY = currentTranslateY.value;
  const deltaTime = Date.now() - dragStartTime.value;
  const velocity = Math.abs(deltaY) / deltaTime;
  
  // Déterminer le nouvel état basé sur la vélocité et la distance
  if (velocity > VELOCITY_THRESHOLD || Math.abs(deltaY) > DISTANCE_THRESHOLD) {
    if (deltaY > 0) {
      // Glissement vers le bas
      if (currentState.value === 'expanded') {
        currentState.value = 'peek';
      } else {
        handleClose();
        return;
      }
    } else {
      // Glissement vers le haut
      if (currentState.value === 'peek') {
        currentState.value = 'expanded';
      }
    }
  }
  
  isDragging.value = false;
  currentTranslateY.value = 0;
};

// Gestion des clics
const handleOverlayClick = (e: Event) => {
  if (e.target === e.currentTarget) {
    handleClose();
  }
};

const handleClose = () => {
  emit('close');
  currentState.value = 'peek';
};

const selectLayer = (layer: 'default' | 'satellite' | 'terrain') => {
  emit('layerChange', layer);
  // Ne pas fermer automatiquement pour permettre d'autres sélections
};

// Gestion des filtres par type
const toggleTypeFilter = (typeId: number | null) => {
  if (typeId === null) {
    // Sélectionner tous les types (vider la sélection)
    selectedTypes.value = [];
  } else {
    // Basculer la sélection du type
    const index = selectedTypes.value.indexOf(typeId);
    if (index > -1) {
      selectedTypes.value.splice(index, 1);
    } else {
      selectedTypes.value.push(typeId);
    }
  }
  emit('typeFilterChange', selectedTypes.value);
};

// Gestion des filtres par statut
const toggleStatusFilter = (statusKey: string | null) => {
  if (statusKey === null) {
    // Sélectionner tous les statuts
    selectedStatuses.value = [];
  } else {
    // Basculer la sélection du statut
    const index = selectedStatuses.value.indexOf(statusKey);
    if (index > -1) {
      selectedStatuses.value.splice(index, 1);
    } else {
      selectedStatuses.value.push(statusKey);
    }
  }
  emit('statusFilterChange', selectedStatuses.value);
};

// Réinitialiser tous les filtres
const resetFilters = () => {
  selectedTypes.value = [];
  selectedStatuses.value = [];
  emit('typeFilterChange', []);
  emit('statusFilterChange', []);
  emit('resetFilters');
};

// Réinitialiser l'état quand le sheet s'ouvre
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    currentState.value = 'expanded';
    currentTranslateY.value = 0;
    // Synchroniser avec les props
    selectedTypes.value = [...props.selectedTypes];
    selectedStatuses.value = [...props.selectedStatuses];
  }
});

// Synchroniser les états locaux avec les props
watch(() => props.selectedTypes, (newVal) => {
  selectedTypes.value = [...newVal];
});

watch(() => props.selectedStatuses, (newVal) => {
  selectedStatuses.value = [...newVal];
});
</script>

<style scoped>
.layer-choice-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(8px);
  z-index: 2000;
  display: flex;
  align-items: flex-end;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.layer-choice-sheet {
  width: 100%;
  background: #ffffff;
  border-radius: 24px 24px 0 0;
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(0, 0, 0, 0.05);
  position: relative;
  max-height: 80vh;
  overflow: hidden;
  animation: slideUp 0.4s cubic-bezier(0.32, 0.72, 0, 1);
}

@keyframes slideUp {
  from { transform: translateY(100%); }
  to { transform: translateY(0); }
}

.sheet-handle {
  padding: 12px 0;
  cursor: grab;
  touch-action: pan-y;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #ffffff;
}

.sheet-handle:active {
  cursor: grabbing;
}

.handle-bar {
  width: 40px;
  height: 4px;
  background: #d1d5db;
  border-radius: 2px;
  transition: background-color 0.2s;
}

.sheet-handle:hover .handle-bar {
  background: #9ca3af;
}

.sheet-content {
  padding: 0 20px 40px;
  max-height: calc(85vh - 60px);
  overflow-y: auto;
}

.sheet-header {
  margin-bottom: 20px;
  text-align: center;
  position: sticky;
  top: 0;
  background: #ffffff;
  padding-bottom: 16px;
  border-bottom: 1px solid #f0f0f0;
  z-index: 10;
}

.sheet-title {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 20px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.title-icon {
  font-size: 24px;
  color: #007AFF;
}

.sheet-subtitle {
  font-size: 14px;
  color: #666;
  margin: 0;
}

/* Sections de filtres */
.filter-section {
  margin-bottom: 32px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
}

.section-icon {
  font-size: 20px;
  color: #007AFF;
}

/* Puces de filtres */
.filter-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.filter-chip {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 20px;
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 4px;
}

.filter-chip:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.filter-chip.active {
  background: #007AFF;
  border-color: #007AFF;
  color: white;
  box-shadow: 0 2px 8px rgba(0, 122, 255, 0.3);
}

.filter-chip.active:hover {
  background: #0056CC;
  border-color: #0056CC;
}

.filter-chip.active .chip-icon {
  color: white;
}

.layer-options {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.layer-option {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border: 2px solid transparent;
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.layer-option:hover {
  background: #f1f5f9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.layer-option.active {
  background: rgba(0, 122, 255, 0.1);
  border-color: #007AFF;
  box-shadow: 0 4px 16px rgba(0, 122, 255, 0.2);
}

.layer-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.layer-option.active .layer-preview {
  background: #007AFF;
}

.layer-icon {
  font-size: 24px;
  color: #666;
}

.layer-option.active .layer-icon {
  color: #ffffff;
}

.layer-info {
  flex: 1;
  min-width: 0;
}

.layer-name {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 4px 0;
}

.layer-description {
  font-size: 14px;
  color: #666;
  margin: 0;
  line-height: 1.4;
}

.layer-option.active .layer-name {
  color: #007AFF;
}

.layer-check {
  flex-shrink: 0;
}

.check-icon {
  font-size: 24px;
  color: #007AFF;
}

.sheet-actions {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
  position: sticky;
  bottom: 0;
  background: #ffffff;
  margin: 0 -20px;
  padding-left: 20px;
  padding-right: 20px;
}

.reset-btn {
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 12px;
  padding: 12px 20px;
  font-size: 16px;
  font-weight: 600;
  color: #dc2626;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
}

.reset-btn:hover {
  background: #fecaca;
  border-color: #fca5a5;
  transform: translateY(-1px);
}

.reset-btn:active {
  transform: translateY(0);
}

.close-btn {
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  padding: 12px 32px;
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
}

.close-btn:hover {
  background: #e5e7eb;
  transform: translateY(-1px);
}

.close-btn:active {
  transform: translateY(0);
}

/* États spécifiques */
.state-peek {
  border-radius: 24px 24px 0 0;
}

.state-expanded {
  border-radius: 20px 20px 0 0;
}

/* Responsive */
@media (max-width: 480px) {
  .sheet-content {
    padding: 0 16px 32px;
  }
  
  .filter-section {
    margin-bottom: 24px;
  }
  
  .section-title {
    font-size: 16px;
  }
  
  .filter-chip {
    padding: 6px 12px;
    font-size: 13px;
  }
  
  .layer-option {
    padding: 12px;
    gap: 12px;
  }
  
  .layer-preview {
    width: 40px;
    height: 40px;
  }
  
  .layer-icon {
    font-size: 20px;
  }
  
  .sheet-actions {
    flex-direction: column;
    gap: 8px;
  }
}
</style>