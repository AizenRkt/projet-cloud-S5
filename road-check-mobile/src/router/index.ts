import { createRouter, createWebHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '@/views/TabsPage.vue'
import Login from '@/views/Login.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    component: Login
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/carte'
      },
      {
        path: 'carte',
        component: () => import('@/views/Carte.vue')
      },
      {
        path: 'recap',
        component: () => import('@/views/Recap.vue')
      },
      {
        path: 'moi',
        component: () => import('@/views/Moi.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
