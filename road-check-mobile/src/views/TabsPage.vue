<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>

      <ion-tab-bar slot="bottom">
        <!-- Carte -->
        <ion-tab-button tab="tab1" href="/tabs/carte">
          <ion-icon aria-hidden="true" :icon="mapOutline" />
          <ion-label>Carte</ion-label>
        </ion-tab-button>

        <!-- Signaler -->
        <ion-tab-button tab="tab2" href="/tabs/tab2">
          <ion-icon aria-hidden="true" :icon="statsChartOutline"/>
          <ion-label>recap</ion-label>
        </ion-tab-button>

        <!-- Profil -->
        <ion-tab-button tab="tab3" href="/tabs/tab3">
          <ion-icon aria-hidden="true" :icon="personCircleOutline" />
          <ion-label>Moi</ion-label>
        </ion-tab-button>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup lang="ts">
import {
  IonTabBar,
  IonTabButton,
  IonTabs,
  IonLabel,
  IonIcon,
  IonPage,
  IonRouterOutlet
} from '@ionic/vue';

import {
  mapOutline,
  warningOutline,
  personCircleOutline,
  addCircleOutline,
  statsChartOutline
} from 'ionicons/icons';
</script>
