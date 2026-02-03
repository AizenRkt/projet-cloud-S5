<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-title>Photo Gallery</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <ion-header collapse="condense">
        <ion-toolbar>
          <ion-title size="large">Photo Gallery</ion-title>
        </ion-toolbar>
      </ion-header>

      <!-- Affichage des images -->
      <div class="photos">
        <ion-img
          v-for="photo in photos"
          :key="photo.filepath"
          :src="photo.webviewPath"
          style="margin: 10px; border-radius: 10px;"
        />
      </div>

      <ion-fab vertical="bottom" horizontal="center" slot="fixed">
        <ion-fab-button @click="addNewToGallery()">
          <ion-icon :icon="camera"></ion-icon>
        </ion-fab-button>
      </ion-fab>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { 
  IonPage, IonHeader, IonFab, IonToolbar, IonTitle, 
  IonContent, IonFabButton, IonIcon, IonImg
} from '@ionic/vue';

import { camera } from 'ionicons/icons';

import { usePhotoGallery } from '@/composables/usePhotoGallery';

const { addNewToGallery, photos } = usePhotoGallery();
</script>
