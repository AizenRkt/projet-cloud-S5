import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import { IonicVue } from '@ionic/vue'
import { defineCustomElements } from '@ionic/pwa-elements/loader'   // ✅ Correct import

/* Core CSS required for Ionic components to work properly */
import '@ionic/vue/css/core.css'

/* Basic CSS */
import '@ionic/vue/css/normalize.css'
import '@ionic/vue/css/structure.css'
import '@ionic/vue/css/typography.css'

/* Optional utilities */
import '@ionic/vue/css/padding.css'
import '@ionic/vue/css/float-elements.css'
import '@ionic/vue/css/text-alignment.css'
import '@ionic/vue/css/text-transformation.css'
import '@ionic/vue/css/flex-utils.css'
import '@ionic/vue/css/display.css'

/* Dark mode system palette */
import '@ionic/vue/css/palettes/dark.system.css'

/* Theme variables */
import './theme/variables.css'

// leaflet
import "leaflet/dist/leaflet.css";

/* IMPORTANT : init des PWA elements (camera, fichiers, etc.) */
defineCustomElements(window)

const app = createApp(App)
  .use(IonicVue)
  .use(router)

router.isReady().then(() => {
  app.mount('#app')
})
