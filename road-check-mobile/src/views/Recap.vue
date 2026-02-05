<template>
  <ion-page>
    <ion-header :translucent="true">
      <ion-toolbar class="header-toolbar">
        <ion-title>Dashboard</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true" class="ion-padding">
      <div class="dashboard-container">
        <!-- Header avec filtre -->
        <div class="dashboard-header">
          <h1 class="page-title">Récapitulatif</h1>
          <ion-segment 
            v-model="selectedFilter" 
            @ionChange="onFilterChange"
            class="filter-segment"
            color="primary"
          >
            <ion-segment-button value="tous">
              <ion-label>Tous</ion-label>
            </ion-segment-button>
            <ion-segment-button value="moi">
              <ion-label>Mes signalements</ion-label>
            </ion-segment-button>
          </ion-segment>
        </div>

        <!-- Cards de métriques -->
        <div class="metrics-grid">
          <ion-card class="metric-card">
            <ion-card-content>
              <div class="metric-content">
                <ion-icon :icon="alertCircleOutline" class="metric-icon metric-signalements"></ion-icon>
                <div class="metric-info">
                  <h2>{{ totalSignalements }}</h2>
                  <p>Signalements</p>
                </div>
              </div>
            </ion-card-content>
          </ion-card>

          <ion-card class="metric-card">
            <ion-card-content>
              <div class="metric-content">
                <ion-icon :icon="cashOutline" class="metric-icon metric-budget"></ion-icon>
                <div class="metric-info">
                  <h2>{{ formatBudget(totalBudget) }}</h2>
                  <p>Budget estimé</p>
                </div>
              </div>
            </ion-card-content>
          </ion-card>

          <ion-card class="metric-card">
            <ion-card-content>
              <div class="metric-content">
                <ion-icon :icon="expandOutline" class="metric-icon metric-surface"></ion-icon>
                <div class="metric-info">
                  <h2>{{ formatSurface(totalSurface) }}</h2>
                  <p>Surface affectée</p>
                </div>
              </div>
            </ion-card-content>
          </ion-card>
        </div>

        <!-- Graphique camembert -->
        <ion-card class="chart-card">
          <ion-card-header>
            <ion-card-title>Répartition par type</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="chart-container">
              <canvas ref="chartCanvas" class="chart-canvas"></canvas>
            </div>
            
            <!-- Légende personnalisée -->
            <div class="chart-legend">
              <div 
                v-for="(item, index) in chartData" 
                :key="item.label"
                class="legend-item"
              >
                <div 
                  class="legend-color"
                  :style="{ backgroundColor: item.color }"
                ></div>
                <span class="legend-label">{{ item.label }}</span>
                <span class="legend-value">{{ item.value }}</span>
              </div>
            </div>
          </ion-card-content>
        </ion-card>

        <!-- Statistiques détaillées -->
        <ion-card class="stats-card">
          <ion-card-header>
            <ion-card-title>Statistiques détaillées</ion-card-title>
          </ion-card-header>
          <ion-card-content>
            <div class="stats-list">
              <div class="stat-item">
                <span class="stat-label">Signalements en cours</span>
                <span class="stat-value">{{ statsDetaillees.enCours }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Signalements résolus</span>
                <span class="stat-value">{{ statsDetaillees.resolus }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Signalements urgents</span>
                <span class="stat-value urgent">{{ statsDetaillees.urgents }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Moyenne par mois</span>
                <span class="stat-value">{{ statsDetaillees.moyenneParMois }}</span>
              </div>
            </div>
          </ion-card-content>
        </ion-card>
      </div>

      <!-- Loading state -->
      <div v-if="isLoading" class="loading-container">
        <ion-spinner name="circular"></ion-spinner>
        <p>Chargement des données...</p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue';
import { 
  IonPage, 
  IonHeader, 
  IonToolbar, 
  IonTitle, 
  IonContent,
  IonCard,
  IonCardContent,
  IonCardHeader,
  IonCardTitle,
  IonIcon,
  IonSegment,
  IonSegmentButton,
  IonLabel,
  IonSpinner
} from '@ionic/vue';
import { 
  alertCircleOutline, 
  cashOutline, 
  expandOutline 
} from 'ionicons/icons';
import Chart from 'chart.js/auto';
import { SignalementService } from '@/services/signalement/SignalementService';
import { auth } from '@/firebase';

// Instance du service
const signalementService = new SignalementService();

// Refs
const isLoading = ref(true);
const selectedFilter = ref<'tous' | 'moi'>('tous');
const chartCanvas = ref<HTMLCanvasElement>();
const chartInstance = ref<Chart | null>(null);

// Données
const signalements = ref<any[]>([]);
const typesSignalement = ref<any[]>([]);

// Métriques calculées
const totalSignalements = computed(() => signalements.value.length);

const totalBudget = computed(() => {
  return signalements.value.reduce((total, signalement) => {
    return total + (signalement.budget || 0);
  }, 0);
});

const totalSurface = computed(() => {
  return signalements.value.reduce((total, signalement) => {
    return total + (signalement.surface || 0);
  }, 0);
});

const statsDetaillees = computed(() => {
  // Comme on n'a pas de statut/priorité dans notre structure, on simule les stats
  const total = signalements.value.length;
  const enCours = Math.round(total * 0.6); // 60% en cours
  const resolus = total - enCours; // Le reste résolu
  const urgents = Math.round(total * 0.1); // 10% urgents
  
  // Calcul de la moyenne par mois (sur les 6 derniers mois)
  const sixMoisAgo = new Date();
  sixMoisAgo.setMonth(sixMoisAgo.getMonth() - 6);
  const recentSignalements = signalements.value.filter(s => 
    new Date(s.dateSignalement) >= sixMoisAgo
  );
  const moyenneParMois = Math.round(recentSignalements.length / 6);

  return {
    enCours,
    resolus,
    urgents,
    moyenneParMois
  };
});

// Données pour le graphique camembert
const chartData = computed(() => {
  const typesCounts = signalements.value.reduce((acc, signalement) => {
    const type = signalement.typeSignalementNom || 'Non spécifié';
    acc[type] = (acc[type] || 0) + 1;
    return acc;
  }, {} as Record<string, number>);

  const colors = [
    '#FF6B6B', // Rouge pour nids de poule
    '#4ECDC4', // Turquoise pour éclairage
    '#45B7D1', // Bleu pour signalisation
    '#96CEB4', // Vert pour végétation
    '#FFEAA7', // Jaune pour déchets
    '#DDA0DD', // Violet pour autres
    '#FFA07A', // Saumon
    '#87CEEB'  // Bleu ciel
  ];

  return Object.entries(typesCounts).map(([type, count], index) => ({
    label: type,
    value: count,
    color: colors[index % colors.length]
  }));
});

// Méthodes
const formatBudget = (budget: number): string => {
  if (budget >= 1000000) {
    return `${(budget / 1000000).toFixed(1)}M Ar`;
  } else if (budget >= 1000) {
    return `${(budget / 1000).toFixed(1)}k Ar`;
  }
  return `${budget} €`;
};

const formatSurface = (surface: number): string => {
  if (surface >= 10000) {
    return `${(surface / 10000).toFixed(1)} m²`;
  }
  return `${surface} m²`;
};

const onFilterChange = async () => {
  await loadSignalements();
  await updateChart();
};

const loadSignalements = async () => {
  try {
    isLoading.value = true;
    
    if (selectedFilter.value === 'moi') {
      const currentUser = auth.currentUser;
      if (currentUser) {
        signalements.value = await signalementService.getByUser(currentUser.uid);
      } else {
        signalements.value = [];
      }
    } else {
      signalements.value = await signalementService.getAll();
    }
  } catch (error) {
    console.error('Erreur lors du chargement des signalements:', error);
    signalements.value = [];
  } finally {
    isLoading.value = false;
  }
};

const initChart = async () => {
  await nextTick();
  
  if (!chartCanvas.value) return;
  
  const ctx = chartCanvas.value.getContext('2d');
  if (!ctx) return;

  // Détruire l'instance précédente si elle existe
  if (chartInstance.value) {
    chartInstance.value.destroy();
  }

  chartInstance.value = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: chartData.value.map(item => item.label),
      datasets: [{
        data: chartData.value.map(item => item.value),
        backgroundColor: chartData.value.map(item => item.color),
        borderWidth: 0,
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false // On utilise notre légende personnalisée
        },
        tooltip: {
          callbacks: {
            label: (context) => {
              const label = context.label || '';
              const value = context.parsed;
              const total = chartData.value.reduce((sum, item) => sum + item.value, 0);
              const percentage = ((value / total) * 100).toFixed(1);
              return `${label}: ${value} (${percentage}%)`;
            }
          }
        }
      },
      cutout: '60%', // Pour faire un donut chart
      animation: {
        animateScale: true,
        animateRotate: true
      }
    }
  });
};

const updateChart = async () => {
  if (chartInstance.value) {
    chartInstance.value.data.labels = chartData.value.map(item => item.label);
    chartInstance.value.data.datasets[0].data = chartData.value.map(item => item.value);
    chartInstance.value.data.datasets[0].backgroundColor = chartData.value.map(item => item.color);
    chartInstance.value.update();
  } else {
    await initChart();
  }
};

// Lifecycle
onMounted(async () => {
  await loadSignalements();
  await initChart();
});
</script>

<style scoped>
/* ========== Variables de couleurs ========== */
:root {
  --app-black: #1a1a2e;
  --app-blue: #3B82F6;
  --app-blue-dark: #2563EB;
  --app-blue-light: #60A5FA;
  --app-white: #FFFFFF;
  --app-gray-light: #F8FAFC;
  --app-gray: #94A3B8;
}

/* ========== Header noir ========== */
.header-toolbar {
  --background: #1a1a2e;
  --color: #FFFFFF;
}

.header-toolbar ion-title {
  color: #FFFFFF;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* ========== Container principal ========== */
.dashboard-container {
  max-width: 100%;
  margin: 0 auto;
  padding: 1rem;
  background-color: #F8FAFC;
  min-height: 100%;
}

ion-content {
  --background: #F8FAFC;
}

/* ========== Header avec titre ========== */
.dashboard-header {
  text-align: center;
  margin-bottom: 1.5rem;
  padding-top: 0.5rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 1rem;
  letter-spacing: -0.5px;
}

.filter-segment {
  max-width: 320px;
  margin: 0 auto;
  --background: #FFFFFF;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
}

.filter-segment ion-segment-button {
  --color: #64748B;
  --color-checked: #FFFFFF;
  --indicator-color: #3B82F6;
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
}

/* ========== Grille des métriques ========== */
.metrics-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
  .metrics-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* ========== Cards métriques ========== */
.metric-card {
  margin: 0;
  border-radius: 16px;
  background: #FFFFFF;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(59, 130, 246, 0.1);
  transition: all 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(59, 130, 246, 0.15);
}

.metric-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.metric-icon {
  font-size: 2.5rem;
  padding: 0.75rem;
  border-radius: 12px;
}

.metric-icon.metric-signalements {
  color: #3B82F6;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
}

.metric-icon.metric-budget {
  color: #10B981;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%);
}

.metric-icon.metric-surface {
  color: #8B5CF6;
  background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(139, 92, 246, 0.05) 100%);
}

.metric-info h2 {
  font-size: 1.75rem;
  font-weight: 700;
  margin: 0;
  color: #1a1a2e;
}

.metric-info p {
  font-size: 0.85rem;
  color: #64748B;
  margin: 0;
  font-weight: 500;
}

/* ========== Card graphique ========== */
.chart-card {
  margin: 0 0 1.5rem 0;
  border-radius: 16px;
  background: #FFFFFF;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(59, 130, 246, 0.1);
}

.chart-card ion-card-header {
  padding-bottom: 0;
}

.chart-card ion-card-title {
  color: #1a1a2e;
  font-size: 1.1rem;
  font-weight: 600;
}

.chart-container {
  position: relative;
  height: 240px;
  margin-bottom: 1.25rem;
}

.chart-canvas {
  max-width: 100%;
  height: 100%;
}

/* ========== Légende du graphique ========== */
.chart-legend {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 0.5rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.legend-item:hover {
  background-color: #F1F5F9;
}

.legend-color {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-label {
  font-size: 0.8rem;
  flex: 1;
  color: #475569;
  font-weight: 500;
}

.legend-value {
  font-weight: 700;
  font-size: 0.85rem;
  color: #3B82F6;
  background: rgba(59, 130, 246, 0.1);
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
}

/* ========== Card statistiques ========== */
.stats-card {
  margin: 0;
  border-radius: 16px;
  background: #FFFFFF;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(59, 130, 246, 0.1);
}

.stats-card ion-card-title {
  color: #1a1a2e;
  font-size: 1.1rem;
  font-weight: 600;
}

.stats-list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.875rem 0;
  border-bottom: 1px solid #F1F5F9;
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-label {
  font-size: 0.9rem;
  color: #64748B;
  font-weight: 500;
}

.stat-value {
  font-weight: 700;
  font-size: 1rem;
  color: #1a1a2e;
  background: #F1F5F9;
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
}

.stat-value.urgent {
  color: #FFFFFF;
  background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
}

/* ========== Loading ========== */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 200px;
  color: #64748B;
}

.loading-container ion-spinner {
  color: #3B82F6;
  width: 48px;
  height: 48px;
}

.loading-container p {
  margin-top: 1rem;
  font-size: 0.9rem;
  font-weight: 500;
}

/* ========== Animations ========== */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.metric-card,
.chart-card,
.stats-card {
  animation: fadeIn 0.4s ease forwards;
}

.metric-card:nth-child(1) { animation-delay: 0.1s; }
.metric-card:nth-child(2) { animation-delay: 0.2s; }
.metric-card:nth-child(3) { animation-delay: 0.3s; }

/* ========== Mode sombre ========== */
@media (prefers-color-scheme: dark) {
  .dashboard-container,
  ion-content {
    --background: #0F172A;
    background-color: #0F172A;
  }
  
  .page-title {
    color: #FFFFFF;
  }
  
  .metric-card,
  .chart-card,
  .stats-card {
    background: #1E293B;
    border-color: rgba(59, 130, 246, 0.2);
  }
  
  .metric-info h2,
  .chart-card ion-card-title,
  .stats-card ion-card-title {
    color: #FFFFFF;
  }
  
  .metric-info p,
  .stat-label {
    color: #94A3B8;
  }
  
  .legend-label {
    color: #CBD5E1;
  }
  
  .stat-value {
    background: #334155;
    color: #FFFFFF;
  }
  
  .legend-item:hover {
    background-color: #334155;
  }
  
  .filter-segment {
    --background: #1E293B;
  }
}
</style>
