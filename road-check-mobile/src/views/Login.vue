<template>
  <ion-page>
    <ion-content class="login-content">
      <!-- Animated road background -->
      <div class="background-gradient">
        <div class="road-lines">
          <div v-for="i in 6" :key="i" class="road-line" :style="{ animationDelay: `${i * 0.3}s` }"></div>
        </div>
        <div class="traffic-lights">
          <div class="traffic-light red"></div>
          <div class="traffic-light yellow"></div>
          <div class="traffic-light green active"></div>
        </div>
      </div>



      <!-- Main card -->
      <div class="login-container">
        <div class="login-card">
          <!-- Logo/Icon with warning theme -->
          <div class="logo-container">
            <div class="logo-circle">
              <div class="warning-badge">
                <ion-icon :icon="warningOutline" class="logo-icon"></ion-icon>
              </div>
              <div class="map-pin">
                <ion-icon :icon="locationOutline"></ion-icon>
              </div>
            </div>
          </div>

          <!-- Title -->
          <h1 class="title">RoadCheck</h1>
          <p class="subtitle">Connectez-vous pour signaler les incidents</p>



          <!-- Inputs -->
          <div class="inputs-container">
            <!-- Email -->
            <div class="input-group">
              <div class="input-wrapper">
                <ion-icon :icon="mailOutline" class="input-icon"></ion-icon>
                <ion-input
                  v-model="email"
                  type="email"
                  placeholder="Email"
                  class="custom-input"
                />
              </div>
            </div>

            <!-- Password -->
            <div class="input-group">
              <div class="input-wrapper">
                <ion-icon :icon="lockClosedOutline" class="input-icon"></ion-icon>
                <ion-input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Mot de passe"
                  class="custom-input"
                />
                <button 
                  @click="showPassword = !showPassword" 
                  class="toggle-password"
                  type="button"
                >
                  <ion-icon :icon="showPassword ? eyeOffOutline : eyeOutline"></ion-icon>
                </button>
              </div>
            </div>

            <!-- Forgot password -->
            <!-- <div v-if="isLogin" class="forgot-password">
              <button type="button">Mot de passe oublié ?</button>
            </div> -->

            <!-- Submit button -->
            <ion-button 
              expand="block" 
              @click="handleSubmit"
              :disabled="isLoading"
              class="submit-button"
            >
              <ion-spinner v-if="isLoading" name="crescent"></ion-spinner>
              <span v-else class="button-content">
                <ion-icon :icon="navigateOutline"></ion-icon>
                Accéder à la carte
              </span>
            </ion-button>
          </div>

          <!-- Features info -->
          <div class="features-info">
            <div class="feature">
              <ion-icon :icon="mapOutline" class="feature-icon"></ion-icon>
              <span>Carte en temps réel</span>
            </div>
            <div class="feature">
              <ion-icon :icon="alertCircleOutline" class="feature-icon"></ion-icon>
              <span>Alertes accidents</span>
            </div>
            <div class="feature">
              <ion-icon :icon="peopleOutline" class="feature-icon"></ion-icon>
              <span>Communauté active</span>
            </div>
          </div>

          <!-- Social login -->
          <!-- <div class="social-section">
            <div class="divider">
              <span>Connexion rapide</span>
            </div>

            <div class="social-buttons">
              <button class="social-btn">
                <ion-icon :icon="logoGoogle"></ion-icon>
                Google
              </button>
              <button class="social-btn">
                <ion-icon :icon="logoApple"></ion-icon>
                Apple
              </button>
              <button class="social-btn">
                <ion-icon :icon="logoFacebook"></ion-icon>
                Facebook
              </button>
            </div>
          </div> -->


        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { login, register } from "@/services/auth";
import { isBlocked, handleLoginAttempt } from "@/services/loginAttempts";
import {
  IonPage,
  IonContent,
  IonInput,
  IonButton,
  IonIcon,
  IonSpinner
} from "@ionic/vue";
import {
  mailOutline,
  lockClosedOutline,
  eyeOutline,
  eyeOffOutline,
  warningOutline,
  locationOutline,
  navigateOutline,
  mapOutline,
  alertCircleOutline,
  peopleOutline
} from "ionicons/icons";

const router = useRouter();
const email = ref("");
const password = ref("");
const showPassword = ref(false);
const isLoading = ref(false);

const handleSubmit = async () => {
  if (isBlocked(email.value)) {
    alert("Compte bloqué temporairement. Réessayez plus tard.");
    return;
  }
  try {
    isLoading.value = true;
    await login(email.value, password.value);
    handleLoginAttempt(email.value, true);
    router.push("/tabs/carte");
  } catch (err) {
    handleLoginAttempt(email.value, false);
    if (isBlocked(email.value)) {
      alert("Compte bloqué après plusieurs tentatives. Réessayez dans quelques minutes.");
    } else {
      alert("Erreur de connexion");
    }
    console.error("Erreur:", err);
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.login-content {
  --background: transparent;
}

/* Sober background */
.background-gradient {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(180deg, #1a1a2e 0%, #16213e 70%, #0f1115 100%);
  overflow: hidden;
  z-index: -1;
}

/* Road lines animation */
.road-lines {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 200px;
  height: 100%;
  perspective: 500px;
}

.road-line {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 8px;
  height: 80px;
  background: #ffd700;
  border-radius: 4px;
  animation: road-move 2s linear infinite;
  opacity: 0.8;
}

.road-line:nth-child(even) {
  background: #ffffff;
  width: 6px;
}

/* Traffic lights */
.traffic-lights {
  position: absolute;
  top: 30px;
  right: 30px;
  display: flex;
  gap: 12px;
  background: rgba(0, 0, 0, 0.3);
  padding: 12px;
  border-radius: 20px;
  backdrop-filter: blur(10px);
}

.traffic-light {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  opacity: 0.3;
  transition: all 0.3s ease;
}

.traffic-light.red {
  background: #ff4444;
  box-shadow: 0 0 10px #ff4444;
}

.traffic-light.yellow {
  background: #ffd700;
  box-shadow: 0 0 10px #ffd700;
}

.traffic-light.green {
  background: #44ff44;
  box-shadow: 0 0 10px #44ff44;
}

.traffic-light.active {
  opacity: 1;
  animation: pulse-light 2s ease-in-out infinite;
}



/* Main container */
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100%;
  padding: 20px;
  position: relative;
  z-index: 1;
}

.login-card {
  position: relative;
  background: rgba(26, 26, 46, 0.85);
  backdrop-filter: blur(20px);
  border: 2px solid rgba(74, 144, 226, 0.3);
  border-radius: 24px;
  padding: 40px 30px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

/* Logo with warning badge */
.logo-container {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.logo-circle {
  position: relative;
  width: 80px;
  height: 80px;
}

.warning-badge {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #4a90e2, #357abd);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 24px rgba(74, 144, 226, 0.3);
}

.logo-icon {
  font-size: 40px;
  color: white;
}

.map-pin {
  position: absolute;
  bottom: -5px;
  right: -5px;
  width: 32px;
  height: 32px;
  background: #4CAF50;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 3px solid #1a1a2e;
  animation: bounce-pin 2s ease-in-out infinite;
}

.map-pin ion-icon {
  font-size: 18px;
  color: white;
}

/* Title */
.title {
  font-size: 28px;
  font-weight: 700;
  color: #ffffff;
  text-align: center;
  margin: 0 0 8px 0;
}

.subtitle {
  color: rgba(255, 255, 255, 0.7);
  text-align: center;
  margin: 0 0 24px 0;
  font-size: 14px;
}



/* Inputs */
.inputs-container {
  margin-bottom: 24px;
}

.input-group {
  position: relative;
  margin-bottom: 16px;
}

.input-wrapper {
  position: relative;
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(74, 144, 226, 0.3);
  border-radius: 16px;
  display: flex;
  align-items: center;
  padding: 0 16px;
  transition: all 0.3s ease;
}

.input-wrapper:hover,
.input-wrapper:focus-within {
  border-color: rgba(74, 144, 226, 0.6);
  background: rgba(255, 255, 255, 0.12);
}

.input-icon {
  font-size: 20px;
  color: #4a90e2;
  margin-right: 12px;
}

.custom-input {
  --background: transparent;
  --color: white;
  --placeholder-color: rgba(255, 255, 255, 0.4);
  --padding-start: 0;
  --padding-end: 0;
  flex: 1;
  font-size: 16px;
}

.toggle-password {
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.5);
  cursor: pointer;
  padding: 8px;
  display: flex;
  align-items: center;
  transition: color 0.3s ease;
}

.toggle-password:hover {
  color: #ffd700;
}

.toggle-password ion-icon {
  font-size: 20px;
}

/* Forgot password */
.forgot-password {
  text-align: right;
  margin-top: 8px;
}

.forgot-password button {
  background: none;
  border: none;
  color: rgba(255, 215, 0, 0.8);
  font-size: 13px;
  cursor: pointer;
  transition: color 0.3s ease;
}

.forgot-password button:hover {
  color: #ffd700;
}

/* Submit button */
.submit-button {
  --background: linear-gradient(135deg, #4a90e2, #357abd);
  --border-radius: 16px;
  --box-shadow: 0 10px 30px rgba(74, 144, 226, 0.4);
  font-weight: 600;
  height: 56px;
  margin-top: 24px;
  transition: all 0.3s ease;
  font-size: 16px;
}

.submit-button:hover {
  transform: translateY(-2px);
  --box-shadow: 0 15px 40px rgba(74, 144, 226, 0.6);
}

.button-content {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
}

.button-content ion-icon {
  font-size: 20px;
}

/* Features info */
.features-info {
  display: flex;
  justify-content: space-around;
  margin: 24px 0;
  padding: 20px;
  background: rgba(74, 144, 226, 0.1);
  border-radius: 16px;
  border: 1px solid rgba(74, 144, 226, 0.2);
}

.feature {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  flex: 1;
}

.feature-icon {
  font-size: 24px;
  color: #ffd700;
}

.feature span {
  color: rgba(255, 255, 255, 0.8);
  font-size: 11px;
  text-align: center;
  font-weight: 500;
}

/* Social section */
.social-section {
  margin-top: 24px;
}

.divider {
  position: relative;
  text-align: center;
  margin-bottom: 16px;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: rgba(255, 107, 53, 0.2);
}

.divider span {
  position: relative;
  background: rgba(26, 26, 46, 0.9);
  padding: 0 16px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 12px;
}

.social-buttons {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.social-btn {
  background: rgba(255, 255, 255, 0.08);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 107, 53, 0.3);
  border-radius: 12px;
  color: white;
  padding: 12px 8px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.social-btn ion-icon {
  font-size: 20px;
}

.social-btn:hover {
  background: rgba(255, 107, 53, 0.2);
  border-color: rgba(255, 107, 53, 0.5);
  transform: translateY(-2px);
}

/* Terms */
.terms {
  margin-top: 20px;
  text-align: center;
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
  line-height: 1.6;
}

.terms a {
  color: #ffd700;
  text-decoration: none;
  font-weight: 500;
}

.terms a:hover {
  text-decoration: underline;
}

/* Animations */
@keyframes road-move {
  0% {
    top: -80px;
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    top: 100%;
    opacity: 0;
  }
}

@keyframes pulse-light {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(0.95);
  }
}

@keyframes rotate-badge {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes bounce-pin {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5px);
  }
}
</style>