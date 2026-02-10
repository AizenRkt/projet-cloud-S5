<template>
  <ion-app>
    <ion-router-outlet />
  </ion-app>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import { IonApp, IonRouterOutlet } from '@ionic/vue';
import { onAuthStateChanged } from 'firebase/auth';
import { auth } from '@/firebase';
import { startStatusWatcher, stopStatusWatcher } from '@/services/statusWatcher';
import { Capacitor } from '@capacitor/core';

let unsubAuth: (() => void) | null = null;

/**
 * Demande toutes les permissions nécessaires au démarrage de l'app.
 * Chaque demande est dans un try/catch isolé pour éviter qu'un échec
 * bloque les suivantes ou fasse crasher l'app.
 */
const requestAllPermissions = async () => {
  if (Capacitor.getPlatform() === 'web') return;

  // 1. Permission de localisation
  try {
    const { Geolocation } = await import('@capacitor/geolocation');
    await Geolocation.requestPermissions();
  } catch (e) {
    console.warn('Permission localisation :', e);
  }

  // 2. Permission caméra + galerie
  try {
    const { Camera } = await import('@capacitor/camera');
    await Camera.requestPermissions({ permissions: ['camera', 'photos'] });
  } catch (e) {
    console.warn('Permission caméra :', e);
  }

  // 3. Permission notifications push — DÉSACTIVÉ (google-services.json manquant)
  // PushNotifications.requestPermissions() est sûr, mais register() crashe
  // fatalement côté natif sans google-services.json → on skip tout.
  // Réactiver quand google-services.json est ajouté dans android/app/
  // try {
  //   if (Capacitor.isPluginAvailable('PushNotifications')) {
  //     const { PushNotifications } = await import('@capacitor/push-notifications');
  //     await PushNotifications.requestPermissions();
  //   }
  // } catch (e) {
  //   console.warn('Permission notifications push :', e);
  // }
};

/**
 * Initialise les notifications push (FCM) si le plugin est disponible
 */
/**
 * Initialise les notifications push — DÉSACTIVÉ tant que google-services.json
 * n'est pas présent dans android/app/. PushNotifications.register() provoque
 * un crash natif fatal (FirebaseApp not initialized) impossible à attraper en JS.
 */
const initNotifications = async () => {
  if (Capacitor.getPlatform() === 'web') return;
  console.warn('[Push] Notifications push désactivées (google-services.json manquant)');
  // Réactiver quand google-services.json est ajouté :
  // try {
  //   if (Capacitor.isPluginAvailable('PushNotifications')) {
  //     const { initPushNotifications } = await import('@/services/notification');
  //     await initPushNotifications();
  //   }
  // } catch (e) {
  //   console.warn('Init notifications push :', e);
  // }
};

onMounted(async () => {
  try {
    // D'abord demander toutes les permissions (séquentiellement, avec await)
    await requestAllPermissions();
  } catch (e) {
    console.warn('Erreur lors de la demande de permissions :', e);
  }

  try {
    // Initialiser les notifications push (enregistrement FCM, listeners)
    await initNotifications();
  } catch (e) {
    console.warn('Erreur init notifications :', e);
  }

  // Démarrer/arrêter le watcher selon l'état de connexion
  unsubAuth = onAuthStateChanged(auth, (user) => {
    if (user) {
      startStatusWatcher();
    } else {
      stopStatusWatcher();
    }
  });
});

onUnmounted(() => {
  unsubAuth?.();
  stopStatusWatcher();
});
</script>
