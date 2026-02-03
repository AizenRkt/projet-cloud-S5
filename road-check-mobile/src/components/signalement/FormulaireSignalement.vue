<template>
  <div 
    class="bottom-sheet-overlay"
    :class="{ 'is-open': isOpen }"
    @click="handleOverlayClick"
  >
    <div 
      ref="sheetRef"
      class="bottom-sheet"
      :class="sheetStateClass"
      :style="sheetStyle"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
      @mousedown="handleMouseDown"
    >
      <!-- Handle pour le drag -->
      <div class="sheet-handle-container" @click="toggleState">
        <div class="sheet-handle"></div>
      </div>

      <!-- Contenu du formulaire -->
      <div class="sheet-content" :class="{ 'scrollable': currentState === 'expanded' }">
        <!-- En-tête avec icône - Zone draggable -->
        <div class="form-header" ref="headerRef">
          <div class="header-icon">
            <ion-icon :icon="alertCircle" class="header-icon-svg"></ion-icon>
          </div>
          <div class="header-text">
            <h2>Nouveau signalement</h2>
            <p class="subtitle">Aidez à améliorer nos routes</p>
          </div>
          <button type="button" class="expand-button" @click="toggleState">
            <ion-icon 
              :icon="currentState === 'expanded' ? chevronDown : chevronUp" 
              class="toggle-icon"
            ></ion-icon>
          </button>
        </div>

        <!-- Informations utilisateur -->
        <div v-if="currentUser" class="user-info">
          <div class="user-avatar">
            <ion-icon :icon="person" class="user-icon"></ion-icon>
          </div>
          <div class="user-details">
            <span class="user-name">{{ currentUser.displayName || currentUser.email }}</span>
            <span class="user-email">{{ currentUser.email }}</span>
          </div>
        </div>
        <div v-else class="user-warning">
          <ion-icon :icon="warning" class="warning-icon"></ion-icon>
          <span>Connexion requise pour créer un signalement</span>
        </div>

        <form @submit.prevent="submitForm" class="form-content">
          <!-- Type de signalement -->
          <div class="form-group" :class="{ 'has-value': form.typeSignalementId, 'has-error': errors.typeSignalementId }">
            <label class="form-label required">Type de problème</label>
            <div class="select-wrapper">
              <div v-if="isLoadingData" class="loading-placeholder">
                <div class="loading-skeleton"></div>
                <span class="loading-text">Chargement des types...</span>
              </div>
              <ion-select 
                v-else
                v-model="form.typeSignalementId" 
                placeholder="Sélectionnez un type"
                interface="action-sheet"
                @ionChange="errors.typeSignalementId = false"
              >
                <ion-select-option
                  v-for="type in typesSignalement"
                  :key="type.id"
                  :value="type.id"
                >
                  {{ type.icon || '📋' }} {{ type.nom }}
                </ion-select-option>
              </ion-select>
            </div>
            <span v-if="errors.typeSignalementId" class="error-message">Ce champ est requis</span>
          </div>

          <!-- Description -->
          <div class="form-group" :class="{ 'has-value': form.description }">
            <label class="form-label">Description détaillée</label>
            <ion-textarea
              v-model="form.description"
              placeholder="Décrivez le problème de manière précise..."
              :rows="4"
              :auto-grow="true"
              class="custom-textarea"
            ></ion-textarea>
            <div class="char-count">{{ form.description.length }} / 500</div>
          </div>

          <!-- Surface et Budget en grid -->
          <div class="form-row">
            <div class="form-group half" :class="{ 'has-value': form.surface }">
              <label class="form-label">
                <ion-icon :icon="square" class="label-icon"></ion-icon>
                Surface (m²)
              </label>
              <ion-input
                type="number"
                min="0"
                step="0.1"
                v-model.number="form.surface"
                placeholder="0.0"
                class="custom-input"
              ></ion-input>
            </div>

            <div class="form-group half" :class="{ 'has-value': form.budget }">
              <label class="form-label">
                <ion-icon :icon="time" class="label-icon"></ion-icon>
                Budget (Ar)
              </label>
              <ion-input
                type="number"
                min="0"
                v-model.number="form.budget"
                placeholder="0"
                class="custom-input"
              ></ion-input>
            </div>
          </div>

          <!-- Entreprise (optionnelle) -->
          <div class="form-group" :class="{ 'has-value': form.entrepriseId }">
            <label class="form-label optional">
              <ion-icon :icon="business" class="label-icon"></ion-icon>
              Entreprise responsable
            </label>
            <div class="select-wrapper">
              <div v-if="isLoadingData" class="loading-placeholder">
                <div class="loading-skeleton"></div>
                <span class="loading-text">Chargement des entreprises...</span>
              </div>
              <ion-select 
                v-else
                v-model="form.entrepriseId" 
                placeholder="Sélectionnez une entreprise"
                interface="action-sheet"
              >
                <ion-select-option
                  v-for="ent in entreprises"
                  :key="ent.id"
                  :value="ent.id"
                >
                  {{ ent.nom }}
                </ion-select-option>
              </ion-select>
            </div>
          </div>

          <!-- Localisation -->
          <div class="location-card">
            <div class="location-header">
              <ion-icon :icon="location" class="location-icon"></ion-icon>
              <span>Localisation</span>
            </div>
            <div class="location-coords">
              <div class="coord-item">
                <span class="coord-label">Lat</span>
                <span class="coord-value">{{ latitude.toFixed(6) }}</span>
              </div>
              <div class="coord-divider"></div>
              <div class="coord-item">
                <span class="coord-label">Long</span>
                <span class="coord-value">{{ longitude.toFixed(6) }}</span>
              </div>
            </div>
          </div>

          <!-- Boutons d'action -->
          <div class="form-actions">
            <button type="button" class="btn-secondary" @click="handleClose">
              Annuler
            </button>
            <ion-button 
              expand="block" 
              type="submit"
              :disabled="isSubmitting"
              class="btn-primary"
            >
              <span v-if="!isSubmitting">Enregistrer</span>
              <span v-else class="loading">
                <span class="spinner"></span>
                Enregistrement...
              </span>
            </ion-button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { IonInput, IonTextarea, IonSelect, IonSelectOption, IonButton, IonIcon } from "@ionic/vue";
import { typeSignalementService, entrepriseService } from "@/services/signalement";
import type { TypeSignalement, Entreprise } from "@/services/signalement/types";
import { getAuth, onAuthStateChanged } from "firebase/auth";
import { 
  chevronUp, 
  chevronDown, 
  alertCircle, 
  person, 
  warning, 
  location, 
  square, 
  time, 
  business 
} from "ionicons/icons";

interface Props {
  latitude: number;
  longitude: number;
  isOpen?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false
});

const emit = defineEmits<{
  close: [];
  submit: [data: any];
}>();

// États du bottom sheet
type SheetState = 'peek' | 'half' | 'expanded';
const currentState = ref<SheetState>('peek');
const sheetRef = ref<HTMLElement | null>(null);
const headerRef = ref<HTMLElement | null>(null);
const isDragging = ref(false);
const dragStartY = ref(0);
const dragStartTime = ref(0);
const currentTranslateY = ref(0);

// Hauteurs pour chaque état (en %)
const SHEET_HEIGHTS = {
  peek: 30,      // Aperçu initial
  half: 60,      // Mi-hauteur
  expanded: 95   // Plein écran
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

// Formulaire
const form = ref({
  typeSignalementId: null as number | null,
  entrepriseId: null as number | null,
  description: "",
  surface: null as number | null,
  budget: null as number | null,
});

const errors = ref({
  typeSignalementId: false
});

const isSubmitting = ref(false);
const isLoadingData = ref(true);

// Données dynamiques depuis Firebase
const typesSignalement = ref<TypeSignalement[]>([]);
const entreprises = ref<Entreprise[]>([]);

// Authentification Firebase
const auth = getAuth();
const currentUser = ref<any>(null);
const userEmail = ref<string | null>(null);
const userUid = ref<string | null>(null);

// Charger les données depuis Firebase
const loadData = async () => {
  try {
    isLoadingData.value = true;
    console.log("Chargement des données Firebase...");
    
    // Charger les types de signalement et entreprises en parallèle
    const [typesData, entreprisesData] = await Promise.all([
      typeSignalementService.getAll(),
      entrepriseService.getAll()
    ]);
    
    typesSignalement.value = typesData;
    entreprises.value = entreprisesData;
    
    console.log("Données chargées:", {
      types: typesData.length,
      entreprises: entreprisesData.length
    });
  } catch (error) {
    console.error("Erreur lors du chargement des données:", error);
    // Fallback vers des données par défaut en cas d'erreur
    typesSignalement.value = [
      { id: 1, code: "fallback-nid", nom: "Trou / Nid de poule", icon: "🕳️" },
      { id: 2, code: "fallback-route", nom: "Route dégradée", icon: "⚠️" },
      { id: 3, code: "fallback-autre", nom: "Autre", icon: "📋" }
    ];
    entreprises.value = [
      { id: 1, code: "fallback-ent", nom: "Entreprise par défaut", logo: "" }
    ];
  } finally {
    isLoadingData.value = false;
  }
};

// Initialiser l'authentification Firebase
const initAuth = () => {
  onAuthStateChanged(auth, (user) => {
    if (user) {
      currentUser.value = user;
      userEmail.value = user.email;
      userUid.value = user.uid;
      console.log("Utilisateur connecté:", {
        uid: user.uid,
        email: user.email,
        displayName: user.displayName
      });
    } else {
      currentUser.value = null;
      userEmail.value = null;
      userUid.value = null;
      console.log("Aucun utilisateur connecté");
    }
  });
};

// Charger les données au montage du composant
onMounted(() => {
  loadData();
  initAuth();
});

// Toggle entre les états avec un clic
const toggleState = () => {
  if (currentState.value === 'peek') {
    currentState.value = 'half';
  } else if (currentState.value === 'half') {
    currentState.value = 'expanded';
  } else {
    currentState.value = 'half';
  }
};

// Gestion du drag - Touch
const handleTouchStart = (e: TouchEvent) => {
  // Permettre le drag depuis n'importe où dans le sheet quand il est en peek
  // Ou seulement depuis le handle/header dans les autres états
  const target = e.target as HTMLElement;
  const isHandle = target.closest('.sheet-handle-container');
  const isHeader = target.closest('.form-header');
  const isInFormContent = target.closest('.form-content');
  
  // En mode peek, permettre le drag depuis n'importe où sauf les inputs
  if (currentState.value === 'peek') {
    if (isInFormContent && (target.tagName === 'ION-INPUT' || target.tagName === 'ION-TEXTAREA' || target.tagName === 'ION-SELECT')) {
      return; // Ne pas drag depuis les champs de formulaire
    }
  } else {
    // Dans les autres modes, seulement depuis le handle ou header
    if (!isHandle && !isHeader) return;
  }
  
  const touch = e.touches[0];
  dragStartY.value = touch.clientY;
  dragStartTime.value = Date.now();
  isDragging.value = true;
  currentTranslateY.value = 0; // Reset la translation
};

const handleTouchMove = (e: TouchEvent) => {
  if (!isDragging.value || !sheetRef.value) return;
  
  const touch = e.touches[0];
  const deltaY = touch.clientY - dragStartY.value;
  
  // Si on est en mode expanded et qu'on scroll dans le contenu, ne pas drag
  if (currentState.value === 'expanded') {
    const content = sheetRef.value.querySelector('.sheet-content') as HTMLElement;
    if (content && content.scrollTop > 0 && deltaY < 0) {
      isDragging.value = false;
      return;
    }
  }
  
  // Permettre le drag vers le haut seulement si on n'est pas déjà en expanded
  if (currentState.value === 'expanded' && deltaY < 0) {
    return;
  }
  
  // Appliquer une résistance pour le drag vers le haut en mode peek
  let adjustedDelta = deltaY;
  if (deltaY < 0 && currentState.value === 'peek') {
    adjustedDelta = deltaY * 0.3; // Résistance
  }
  
  currentTranslateY.value = Math.max(-50, adjustedDelta); // Permettre un petit drag négatif
  
  // Empêcher le scroll de la page pendant le drag
  e.preventDefault();
};

const handleTouchEnd = () => {
  if (!isDragging.value) return;
  
  const deltaY = currentTranslateY.value;
  const deltaTime = Date.now() - dragStartTime.value;
  const velocity = Math.abs(deltaY) / deltaTime;
  
  // Déterminer le nouvel état basé sur la distance et la vélocité
  if (currentState.value === 'peek') {
    if (deltaY > 80 || (deltaY > 30 && velocity > 0.3)) {
      handleClose(); // Fermer si drag vers le bas
    } else if (deltaY < -30 || (deltaY < -10 && velocity > 0.3)) {
      currentState.value = 'half'; // Ouvrir à moitié si drag vers le haut
    }
  } else if (currentState.value === 'half') {
    if (deltaY > 100 || (deltaY > 50 && velocity > 0.3)) {
      currentState.value = 'peek';
    } else if (deltaY < -50 || (deltaY < -20 && velocity > 0.3)) {
      currentState.value = 'expanded';
    }
  } else if (currentState.value === 'expanded') {
    if (deltaY > 150 || (deltaY > 80 && velocity > 0.3)) {
      currentState.value = 'half';
    }
  }
  
  isDragging.value = false;
  currentTranslateY.value = 0;
};

// Gestion du drag - Mouse (pour desktop)
const handleMouseDown = (e: MouseEvent) => {
  const target = e.target as HTMLElement;
  const isHandle = target.closest('.sheet-handle-container');
  const isHeader = target.closest('.form-header');
  const isInFormContent = target.closest('.form-content');
  
  // Même logique que pour le touch
  if (currentState.value === 'peek') {
    if (isInFormContent && (target.tagName === 'ION-INPUT' || target.tagName === 'ION-TEXTAREA' || target.tagName === 'ION-SELECT')) {
      return;
    }
  } else {
    if (!isHandle && !isHeader) return;
  }
  
  dragStartY.value = e.clientY;
  dragStartTime.value = Date.now();
  isDragging.value = true;
  currentTranslateY.value = 0;
  
  const handleMouseMove = (moveEvent: MouseEvent) => {
    if (!isDragging.value) return;
    
    const deltaY = moveEvent.clientY - dragStartY.value;
    
    if (currentState.value === 'expanded' && deltaY < 0) {
      return;
    }
    
    // Appliquer la même résistance qu'au touch
    let adjustedDelta = deltaY;
    if (deltaY < 0 && currentState.value === 'peek') {
      adjustedDelta = deltaY * 0.3;
    }
    
    currentTranslateY.value = Math.max(-50, adjustedDelta);
  };
  
  const handleMouseUp = () => {
    handleTouchEnd(); // Réutiliser la même logique
    document.removeEventListener('mousemove', handleMouseMove);
    document.removeEventListener('mouseup', handleMouseUp);
  };
  
  document.addEventListener('mousemove', handleMouseMove);
  document.addEventListener('mouseup', handleMouseUp);
};

// Clic sur l'overlay pour fermer
const handleOverlayClick = (e: MouseEvent) => {
  if (e.target === e.currentTarget) {
    handleClose();
  }
};

// Fermer le sheet
const handleClose = () => {
  emit('close');
  resetForm();
};

// Soumettre le formulaire
const submitForm = async () => {
  // Validation
  if (!form.value.typeSignalementId) {
    errors.value.typeSignalementId = true;
    return;
  }

  // Vérifier si l'utilisateur est connecté
  if (!currentUser.value) {
    alert("Vous devez être connecté pour créer un signalement");
    return;
  }

  isSubmitting.value = true;

  try {
    // Récupérer les noms correspondants aux IDs sélectionnés
    const selectedType = typesSignalement.value.find(t => t.id === form.value.typeSignalementId);
    const selectedEntreprise = form.value.entrepriseId 
      ? entreprises.value.find(e => e.id === form.value.entrepriseId) 
      : null;

    const signalementData = {
      typeSignalementId: form.value.typeSignalementId,
      typeSignalementNom: selectedType?.nom,
      entrepriseId: form.value.entrepriseId,
      entrepriseNom: selectedEntreprise?.nom,
      description: form.value.description,
      surface: form.value.surface,
      budget: form.value.budget,
      latitude: props.latitude,
      longitude: props.longitude,
      utilisateurId: userUid.value, // UID de l'utilisateur
      utilisateurEmail: userEmail.value // Email de l'utilisateur (optionnel)
    };

    console.log("Données à envoyer:", signalementData);
    console.log("Utilisateur:", {
      uid: userUid.value,
      email: userEmail.value,
      displayName: currentUser.value?.displayName || 'N/A'
    });
    
    emit('submit', signalementData);
    
    // Réinitialiser le formulaire après soumission
    resetForm();
    
  } catch (error) {
    console.error("Erreur lors de la préparation des données:", error);
  } finally {
    isSubmitting.value = false;
  }
};

const resetForm = () => {
  form.value = {
    typeSignalementId: null,
    entrepriseId: null,
    description: "",
    surface: null,
    budget: null,
  };
  errors.value.typeSignalementId = false;
  currentState.value = 'peek';
};

// Réinitialiser l'état quand le sheet s'ouvre et recharger les données si nécessaire
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    currentState.value = 'peek';
    // Recharger les données si elles ne sont pas encore chargées
    if (typesSignalement.value.length === 0 || entreprises.value.length === 0) {
      loadData();
    }
  }
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.bottom-sheet-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0);
  z-index: 1000;
  pointer-events: none;
  transition: background 0.3s ease;
}

.bottom-sheet-overlay.is-open {
  background: rgba(0, 0, 0, 0.4);
  pointer-events: all;
}

.bottom-sheet {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-radius: 24px 24px 0 0;
  box-shadow: 0 -4px 32px rgba(0, 0, 0, 0.12);
  z-index: 1001;
  height: 30vh;
  display: flex;
  flex-direction: column;
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
  transform: translateY(100%);
  transition: all 0.4s cubic-bezier(0.32, 0.72, 0, 1);
}

.bottom-sheet-overlay.is-open .bottom-sheet {
  transform: translateY(0);
}

.sheet-handle-container {
  padding: 12px 0 8px;
  display: flex;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  user-select: none;
  -webkit-user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.sheet-handle-container:active {
  cursor: pointer;
}

.sheet-handle {
  width: 40px;
  height: 4px;
  background: #d0d0d0;
  border-radius: 2px;
  transition: all 0.2s ease;
}

.sheet-handle-container:hover .sheet-handle {
  background: #b0b0b0;
  width: 50px;
}

.sheet-handle-container:active .sheet-handle {
  background: #999;
  width: 45px;
}

.sheet-content {
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0 20px 20px;
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

.sheet-content.scrollable {
  overflow-y: scroll;
}

.bottom-sheet.state-peek .sheet-content {
  overflow-y: hidden;
}

.bottom-sheet.state-half .sheet-content {
  overflow-y: auto;
}

/* En-tête */
.form-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
  cursor: pointer;
  user-select: none;
  -webkit-user-select: none;
  position: relative;
}

.form-header:active {
  opacity: 0.9;
}

.header-icon {
  width: 56px;
  height: 56px;
  min-width: 56px;
  background: linear-gradient(135deg, #d32f2f 0%, #e64545 100%);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 16px rgba(211, 47, 47, 0.25);
}

.header-icon-svg {
  width: 28px;
  height: 28px;
  color: white;
}

.header-text h2 {
  margin: 0 0 4px;
  font-size: 22px;
  font-weight: 700;
  color: #1a1a1a;
  letter-spacing: -0.5px;
}

.subtitle {
  margin: 0;
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.expand-button {
  margin-left: auto;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f0f0f0;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.expand-button:hover {
  background: #e0e0e0;
}

.expand-button:active {
  transform: scale(0.95);
}

.toggle-icon {
  width: 18px;
  height: 18px;
  color: #666;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.expand-button:hover .toggle-icon {
  transform: scale(1.1);
}

/* Informations utilisateur */
.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
  background: linear-gradient(135deg, #4CAF50 0%, #66BB6A 100%);
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(76, 175, 80, 0.2);
}

.user-avatar {
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-icon {
  width: 20px;
  height: 20px;
  color: white;
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: white;
  line-height: 1.2;
}

.user-email {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.2;
}

.user-warning {
  display: flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #FF9800 0%, #FFB74D 100%);
  border-radius: 12px;
  padding: 12px 16px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(255, 152, 0, 0.2);
}

.warning-icon {
  width: 20px;
  height: 20px;
  color: white;
  flex-shrink: 0;
}

.user-warning span {
  font-size: 13px;
  font-weight: 500;
  color: white;
}

/* Indicateurs de chargement */
.loading-placeholder {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  min-height: 52px;
}

.loading-skeleton {
  width: 24px;
  height: 24px;
  background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 50%, #e9ecef 75%);
  background-size: 200% 100%;
  border-radius: 6px;
  animation: shimmer 1.5s infinite;
}

.loading-text {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Formulaire */
.form-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.label-icon {
  width: 14px;
  height: 14px;
  color: #d32f2f;
}

.form-label.required::after {
  content: '*';
  color: #d32f2f;
  margin-left: 2px;
  font-weight: 700;
}

.form-label.optional::after {
  content: '(optionnel)';
  color: #666;
  font-size: 11px;
  font-weight: 400;
  text-transform: none;
  letter-spacing: 0;
  margin-left: 4px;
}

.select-wrapper {
  position: relative;
}

ion-select {
  width: 100%;
  --padding-start: 16px;
  --padding-end: 16px;
  --padding-top: 14px;
  --padding-bottom: 14px;
  --border-radius: 12px;
  --background: #f8f9fa;
  --color: #1a1a1a;
  --placeholder-color: #999;
  --placeholder-opacity: 1;
  border: 2px solid #e9ecef;
  font-size: 15px;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

ion-select:focus,
.form-group.has-value ion-select {
  --background: white;
  --color: #1a1a1a;
  border-color: #d32f2f;
  box-shadow: 0 0 0 4px rgba(211, 47, 47, 0.08);
}

ion-textarea,
ion-input {
  --padding-start: 16px;
  --padding-end: 16px;
  --padding-top: 14px;
  --padding-bottom: 14px;
  --border-radius: 12px;
  --background: #f8f9fa;
  --color: #1a1a1a;
  --placeholder-color: #999;
  border: 2px solid #e9ecef;
  font-size: 15px;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

ion-textarea:focus,
ion-input:focus,
.form-group.has-value ion-textarea,
.form-group.has-value ion-input {
  --background: white;
  --color: #1a1a1a;
  border-color: #d32f2f;
  box-shadow: 0 0 0 4px rgba(211, 47, 47, 0.08);
}

.custom-textarea {
  min-height: 100px;
}

.char-count {
  text-align: right;
  font-size: 11px;
  color: #999;
  margin-top: 6px;
  font-weight: 500;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.form-group.half {
  margin: 0;
}

/* Localisation */
.location-card {
  background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(26, 35, 126, 0.2);
}

.location-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  color: white;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.location-icon {
  width: 18px;
  height: 18px;
}

.location-coords {
  display: flex;
  align-items: center;
  gap: 16px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 14px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.coord-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.coord-label {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.7);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.coord-value {
  font-size: 15px;
  color: white;
  font-weight: 700;
  font-family: 'SF Mono', Consolas, monospace;
}

.coord-divider {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.2);
}

/* Actions */
.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 8px;
  padding-bottom: 20px;
}

.btn-secondary {
  flex: 0 0 auto;
  padding: 14px 20px;
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  color: #495057;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-family: inherit;
}

.btn-secondary:hover {
  background: #e9ecef;
  border-color: #dee2e6;
}

.btn-secondary:active {
  transform: scale(0.98);
}

ion-button.btn-primary {
  flex: 1;
  --background: linear-gradient(135deg, #d32f2f 0%, #e64545 100%);
  --background-hover: linear-gradient(135deg, #c62828 0%, #d32f2f 100%);
  --background-activated: linear-gradient(135deg, #b71c1c 0%, #c62828 100%);
  --border-radius: 12px;
  --box-shadow: 0 4px 16px rgba(211, 47, 47, 0.3);
  --padding-top: 14px;
  --padding-bottom: 14px;
  font-weight: 700;
  font-size: 14px;
  letter-spacing: 0.3px;
  text-transform: uppercase;
}

.loading {
  display: flex;
  align-items: center;
  gap: 8px;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-message {
  font-size: 12px;
  color: #d32f2f;
  margin-top: 6px;
  font-weight: 500;
}

.form-group.has-error ion-select,
.form-group.has-error ion-input,
.form-group.has-error ion-textarea {
  border-color: #d32f2f;
  --background: #fff5f5;
  --color: #1a1a1a;
}

/* Styles supplémentaires pour assurer la visibilité du texte */
ion-select::part(text) {
  color: #1a1a1a !important;
}

ion-select::part(placeholder) {
  color: #999 !important;
}

ion-input input {
  color: #1a1a1a !important;
}

ion-textarea textarea {
  color: #1a1a1a !important;
}

/* Force la couleur du texte dans les champs actifs */
.form-group.has-value ion-select::part(text),
.form-group.has-value ion-input input,
.form-group.has-value ion-textarea textarea {
  color: #1a1a1a !important;
}

/* Scrollbar custom */
.sheet-content::-webkit-scrollbar {
  width: 6px;
}

.sheet-content::-webkit-scrollbar-track {
  background: transparent;
}

.sheet-content::-webkit-scrollbar-thumb {
  background: #d0d0d0;
  border-radius: 3px;
}

.sheet-content::-webkit-scrollbar-thumb:hover {
  background: #b0b0b0;
}
</style>