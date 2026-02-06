import { PushNotifications } from "@capacitor/push-notifications";
import { Capacitor } from "@capacitor/core";
import { getAuth } from "firebase/auth";
import { getFirestore, doc, setDoc } from "firebase/firestore";
import { toastController } from "@ionic/vue";

const auth = getAuth();
const db = getFirestore();

export const initPushNotifications = async () => {
  if (Capacitor.getPlatform() === "web") {
    console.log("Notifications push non disponibles sur web");
    return;
  }

  try {
    const perm = await PushNotifications.requestPermissions();
    if (perm.receive !== "granted") {
      console.log("Permission notifications refusée");
      return;
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

    // Notification reçue quand l'app est ouverte → afficher un toast
    PushNotifications.addListener("pushNotificationReceived", async (notification) => {
      console.log("Notification reçue :", notification);
      
      const toast = await toastController.create({
        header: notification.title || "Notification",
        message: notification.body || "",
        duration: 4000,
        position: "top",
        color: "primary",
        buttons: [
          {
            text: "Voir",
            role: "info",
          },
        ],
      });
      await toast.present();
    });

    // Notification tapée → naviguer vers le signalement
    PushNotifications.addListener(
      "pushNotificationActionPerformed",
      (action) => {
        console.log("Action notification :", action);
        
        const data = action.notification.data;
        if (data?.type === "status_change" && data?.signalementId) {
          // Naviguer vers la page Moi pour voir les signalements
          window.location.href = "/tabs/moi";
        }
      }
    );

    console.log("Notifications push initialisées");
  } catch (err) {
    console.error("Erreur initPushNotifications :", err);
  }
};
