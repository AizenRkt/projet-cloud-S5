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

let unsubAuth: (() => void) | null = null;

onMounted(() => {
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
