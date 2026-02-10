import { Capacitor } from "@capacitor/core";
import { getAuth } from "firebase/auth";
import { getFirestore, doc, setDoc } from "firebase/firestore";
import { toastController } from "@ionic/vue";

const auth = getAuth();
const db = getFirestore();

/**
 * Initialise les notifications push (FCM).
 *
 * ⚠️ DÉSACTIVÉ : PushNotifications.register() provoque un crash natif fatal
 * (IllegalStateException: Default FirebaseApp is not initialized) car
 * `google-services.json` est absent du dossier `android/app/`.
 *
 * Pour réactiver :
 * 1. Aller dans la Console Firebase → Paramètres → App Android
 * 2. Télécharger google-services.json
 * 3. Le placer dans road-check-mobile/android/app/google-services.json
 * 4. Décommenter le code ci-dessous
 */
export const initPushNotifications = async () => {
  console.warn(
    "[Push] Notifications push désactivées — google-services.json manquant. " +
    "Ajoutez-le dans android/app/ puis réactivez le code dans notification.ts"
  );
  return;

  // ----- CODE À RÉACTIVER QUAND google-services.json EST PRÉSENT -----
  /*
  if (Capacitor.getPlatform() === "web") {
    console.log("Notifications push non disponibles sur web");
    return;
  }

  if (!Capacitor.isPluginAvailable('PushNotifications')) {
    console.warn("Plugin PushNotifications non disponible");
    return;
  }

  try {
    const { PushNotifications } = await import("@capacitor/push-notifications");

    const perm = await PushNotifications.checkPermissions();
    if (perm.receive !== "granted") {
      const reqPerm = await PushNotifications.requestPermissions();
      if (reqPerm.receive !== "granted") {
        console.log("Permission notifications refusée");
        return;
      }
    }

    await PushNotifications.register();

    PushNotifications.addListener("registration", async (token) => {
      console.log("FCM Token :", token.value);
      const user = auth.currentUser;
      if (user) {
        await setDoc(doc(db, "users", user.uid), {
          email: user.email,
          fcmToken: token.value,
        }, { merge: true });
        console.log("Token FCM enregistré dans Firestore");
      }
    });

    PushNotifications.addListener("pushNotificationReceived", async (notification) => {
      console.log("Notification reçue :", notification);
      const toast = await toastController.create({
        header: notification.title || "Notification",
        message: notification.body || "",
        duration: 4000,
        position: "top",
        color: "primary",
        buttons: [{ text: "Voir", role: "info" }],
      });
      await toast.present();
    });

    PushNotifications.addListener("pushNotificationActionPerformed", (action) => {
      console.log("Action notification :", action);
      const data = action.notification.data;
      if (data?.type === "status_change" && data?.signalementId) {
        window.location.href = "/tabs/moi";
      }
    });

    console.log("Notifications push initialisées");
  } catch (err) {
    console.error("Erreur initPushNotifications :", err);
  }
  */
};
