<template>
  <div 
    class="sidebar-overlay"
    :class="{ 'is-open': isOpen }"
    @click="handleOverlayClick"
  >
    <div 
      class="sidebar"
      :class="{ 'is-open': isOpen }"
    >
      <!-- Header du profil -->
      <div class="sidebar-header">
        <div class="profile-avatar-large">
          <img 
            src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120&h=120&fit=crop&crop=face" 
            alt="Photo de profil" 
            class="avatar-image-large"
          />
        </div>
        <button 
          type="button" 
          class="close-button"
          @click="$emit('close')"
        >
          <ion-icon :icon="closeOutline" class="close-icon"></ion-icon>
        </button>
      </div>

      <!-- Menu -->
      <div class="sidebar-menu">
        <!-- Options du menu -->
        <div class="menu-section">
          <h3 class="section-title">Options</h3>
          
          <!-- Option Profil -->
          <button type="button" class="menu-item">
            <div class="menu-item-icon">
              <ion-icon :icon="personOutline" class="item-icon"></ion-icon>
            </div>
            <div class="menu-item-content">
              <span class="menu-item-title">Mon profil</span>
              <span class="menu-item-subtitle">Modifier mes informations</span>
            </div>
            <div class="menu-item-arrow">
              <ion-icon :icon="chevronForwardOutline" class="arrow-icon"></ion-icon>
            </div>
          </button>

          <!-- Option Paramètres -->
          <button type="button" class="menu-item">
            <div class="menu-item-icon">
              <ion-icon :icon="settingsOutline" class="item-icon"></ion-icon>
            </div>
            <div class="menu-item-content">
              <span class="menu-item-title">Paramètres</span>
              <span class="menu-item-subtitle">Configuration de l'app</span>
            </div>
            <div class="menu-item-arrow">
              <ion-icon :icon="chevronForwardOutline" class="arrow-icon"></ion-icon>
            </div>
          </button>

          <!-- Option Aide -->
          <button type="button" class="menu-item">
            <div class="menu-item-icon">
              <ion-icon :icon="helpCircleOutline" class="item-icon"></ion-icon>
            </div>
            <div class="menu-item-content">
              <span class="menu-item-title">Aide</span>
              <span class="menu-item-subtitle">Support et FAQ</span>
            </div>
            <div class="menu-item-arrow">
              <ion-icon :icon="chevronForwardOutline" class="arrow-icon"></ion-icon>
            </div>
          </button>
        </div>

        <!-- Section Thème -->
        <div class="menu-section">
          <h3 class="section-title">Apparence</h3>
          
          <div class="theme-selector">
            <button 
              type="button" 
              class="theme-option"
              :class="{ 'is-active': currentTheme === 'system' }"
              @click="setTheme('system')"
            >
              <div class="theme-option-icon">
                <ion-icon :icon="phonePortraitOutline" class="theme-icon"></ion-icon>
              </div>
              <span class="theme-option-label">Système</span>
            </button>
            
            <button 
              type="button" 
              class="theme-option"
              :class="{ 'is-active': currentTheme === 'light' }"
              @click="setTheme('light')"
            >
              <div class="theme-option-icon">
                <ion-icon :icon="sunnyOutline" class="theme-icon"></ion-icon>
              </div>
              <span class="theme-option-label">Jour</span>
            </button>
            
            <button 
              type="button" 
              class="theme-option"
              :class="{ 'is-active': currentTheme === 'dark' }"
              @click="setTheme('dark')"
            >
              <div class="theme-option-icon">
                <ion-icon :icon="moonOutline" class="theme-icon"></ion-icon>
              </div>
              <span class="theme-option-label">Nuit</span>
            </button>
          </div>
        </div>

        <!-- Section déconnexion -->
        <div class="logout-section">
          <button 
            type="button" 
            class="logout-button"
            @click="handleLogout"
            :disabled="isLoggingOut"
          >
            <div class="logout-icon">
              <ion-spinner 
                v-if="isLoggingOut" 
                name="dots" 
                class="logout-spinner"
              ></ion-spinner>
              <ion-icon 
                v-else
                :icon="logOutOutline" 
                class="logout-icon-svg"
              ></ion-icon>
            </div>
            <div class="logout-content">
              <span class="logout-title">
                {{ isLoggingOut ? 'Déconnexion...' : 'Se déconnecter' }}
              </span>
              <span class="logout-subtitle">Quitter mon compte</span>
            </div>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonIcon, IonSpinner } from '@ionic/vue';
import {
  closeOutline,
  personOutline,
  settingsOutline,
  helpCircleOutline,
  logOutOutline,
  chevronForwardOutline,
  sunnyOutline,
  moonOutline,
  phonePortraitOutline
} from 'ionicons/icons';
import { logout } from '@/services/auth';
import type { User } from 'firebase/auth';

interface Props {
  isOpen: boolean;
  currentUser?: User | null;
  signalements?: any[];
  recentCount?: number;
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  currentUser: null,
  signalements: () => [],
  recentCount: 0
});

const emit = defineEmits<{
  close: [];
  logout: [];
}>();

const router = useRouter();
const isLoggingOut = ref(false);

// Gestion du thème
type ThemeMode = 'system' | 'light' | 'dark';
const currentTheme = ref<ThemeMode>('system');

// Charger le thème sauvegardé
const loadSavedTheme = () => {
  const savedTheme = localStorage.getItem('app-theme') as ThemeMode | null;
  if (savedTheme && ['system', 'light', 'dark'].includes(savedTheme)) {
    currentTheme.value = savedTheme;
    applyTheme(savedTheme);
  }
};

// Appliquer le thème
const applyTheme = (theme: ThemeMode) => {
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  
  if (theme === 'dark' || (theme === 'system' && prefersDark)) {
    document.documentElement.classList.add('dark');
    document.body.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
    document.body.classList.remove('dark');
  }
};

// Changer le thème
const setTheme = (theme: ThemeMode) => {
  currentTheme.value = theme;
  localStorage.setItem('app-theme', theme);
  applyTheme(theme);
};

// Écouter les changements du système
const setupSystemThemeListener = () => {
  const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
  mediaQuery.addEventListener('change', () => {
    if (currentTheme.value === 'system') {
      applyTheme('system');
    }
  });
};

// Initialiser le thème au montage
loadSavedTheme();
setupSystemThemeListener();

// Fermer la sidebar en cliquant sur l'overlay
const handleOverlayClick = (e: MouseEvent) => {
  if (e.target === e.currentTarget) {
    emit('close');
  }
};

// Gérer la déconnexion
const handleLogout = async () => {
  if (isLoggingOut.value) return;
  
  try {
    isLoggingOut.value = true;
    await logout();
    emit('logout');
    emit('close');
    
    router.push('/');
  } catch (error) {
    console.error('Erreur lors de la déconnexion:', error);
    alert('Erreur lors de la déconnexion. Veuillez réessayer.');
  } finally {
    isLoggingOut.value = false;
  }
};
</script>

<style scoped>
/* Overlay */
.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0);
  z-index: 2000;
  pointer-events: none;
  transition: background 0.3s ease;
}

.sidebar-overlay.is-open {
  background: rgba(0, 0, 0, 0.5);
  pointer-events: all;
}

/* Sidebar */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 70%;
  background: #ffffff;
  z-index: 2001;
  display: flex;
  flex-direction: column;
  transform: translateX(-100%);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
}

.sidebar.is-open {
  transform: translateX(0);
}

/* Header */
.sidebar-header {
  position: relative;
  background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
  padding: env(safe-area-inset-top, 0) 20px 24px 20px;
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 16px;
}

.close-button {
  position: absolute;
  top: calc(env(safe-area-inset-top, 0px) + 12px);
  right: 16px;
  width: 36px;
  height: 36px;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.close-button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

.close-button:active {
  transform: scale(0.95);
}

.close-icon {
  width: 20px;
  height: 20px;
  color: white;
}

.profile-avatar-large {
  margin-top: 20px;
}

.avatar-image-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 4px solid rgba(255, 255, 255, 0.2);
  object-fit: cover;
}

.profile-details {
  display: flex;
  flex-direction:row;
  align-items: center;
}

.profile-name {
  font-size: 20px;
  font-weight: 700;
  margin: 0;
  color: white;
}

.profile-email {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
  font-weight: 500;
}

/* Menu */
.sidebar-menu {
  flex: 1;
  overflow-y: auto;
  padding: 20px 0;
}

.section-title {
  font-size: 13px;
  font-weight: 700;
  color: #8E8E93;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 16px 20px;
}

/* Stats */
.stats-section {
  margin-bottom: 32px;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 0 20px;
}

.stat-card {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 16px;
  text-align: center;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #007AFF;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;
  color: #8E8E93;
  font-weight: 500;
}

/* Menu items */
.menu-section {
  margin-bottom: 32px;
}

.menu-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  transition: background 0.2s ease;
  text-align: left;
}

.menu-item:hover {
  background: #f8f9fa;
}

.menu-item:active {
  background: #f0f0f0;
}

.menu-item-icon {
  width: 40px;
  height: 40px;
  background: #f8f9fa;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.item-icon {
  width: 20px;
  height: 20px;
  color: #007AFF;
}

.menu-item-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.menu-item-title {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  line-height: 1.3;
}

.menu-item-subtitle {
  font-size: 13px;
  color: #8E8E93;
  line-height: 1.3;
}

.menu-item-arrow {
  flex-shrink: 0;
}

.arrow-icon {
  width: 18px;
  height: 18px;
  color: #C7C7CC;
}

/* Logout */
.logout-section {
  margin: 20px;
}

.logout-button {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: linear-gradient(135deg, #FF3B30 0%, #FF6B6B 100%);
  border: none;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: left;
  box-shadow: 0 4px 16px rgba(255, 59, 48, 0.25);
}

.logout-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(255, 59, 48, 0.35);
}

.logout-button:active {
  transform: translateY(0);
}

.logout-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none !important;
}

.logout-icon {
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.logout-icon-svg {
  width: 20px;
  height: 20px;
  color: white;
}

.logout-spinner {
  --color: white;
  transform: scale(0.8);
}

.logout-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.logout-title {
  font-size: 16px;
  font-weight: 600;
  color: white;
  line-height: 1.3;
}

.logout-subtitle {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.3;
}

/* Footer */
.sidebar-footer {
  padding: 20px;
  border-top: 1px solid #f0f0f0;
  text-align: center;
  background: #f8f9fa;
}

.app-version {
  font-size: 12px;
  color: #8E8E93;
  margin: 0 0 4px 0;
  font-weight: 600;
}

.app-info {
  font-size: 11px;
  color: #C7C7CC;
  margin: 0;
  font-weight: 500;
}

/* Theme Selector */
.theme-selector {
  display: flex;
  gap: 8px;
  padding: 0 16px;
}

.theme-option {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 16px 12px;
  background: #f5f5f7;
  border: 2px solid transparent;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.theme-option:hover {
  background: #e8e8ed;
}

.theme-option.is-active {
  background: rgba(0, 122, 255, 0.1);
  border-color: #007AFF;
}

.theme-option-icon {
  width: 40px;
  height: 40px;
  background: #ffffff;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.theme-option.is-active .theme-option-icon {
  background: #007AFF;
}

.theme-icon {
  font-size: 20px;
  color: #666;
}

.theme-option.is-active .theme-icon {
  color: #ffffff;
}

.theme-option-label {
  font-size: 12px;
  font-weight: 600;
  color: #666;
  text-align: center;
}

.theme-option.is-active .theme-option-label {
  color: #007AFF;
}

/* Mode sombre - Sidebar */
:global(.dark) .sidebar {
  background: #1c1c1e;
}

:global(.dark) .section-title {
  color: #8E8E93;
}

:global(.dark) .menu-item {
  background: #2c2c2e;
}

:global(.dark) .menu-item:hover {
  background: #3c3c3e;
}

:global(.dark) .menu-item-title {
  color: #ffffff;
}

:global(.dark) .menu-item-subtitle {
  color: #8E8E93;
}

:global(.dark) .item-icon {
  color: #007AFF;
}

:global(.dark) .theme-option {
  background: #2c2c2e;
}

:global(.dark) .theme-option:hover {
  background: #3c3c3e;
}

:global(.dark) .theme-option.is-active {
  background: rgba(0, 122, 255, 0.2);
}

:global(.dark) .theme-option-icon {
  background: #3c3c3e;
  box-shadow: none;
}

:global(.dark) .theme-icon {
  color: #8E8E93;
}

:global(.dark) .theme-option-label {
  color: #8E8E93;
}

:global(.dark) .theme-option.is-active .theme-option-label {
  color: #007AFF;
}

/* Responsive */
@media (max-width: 480px) {
  .sidebar {
    width: 80%;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .theme-selector {
    padding: 0 12px;
  }
  
  .theme-option {
    padding: 12px 8px;
  }
  
  .theme-option-icon {
    width: 36px;
    height: 36px;
  }
  
  .theme-icon {
    font-size: 18px;
  }
  
  .theme-option-label {
    font-size: 11px;
  }
}
</style>