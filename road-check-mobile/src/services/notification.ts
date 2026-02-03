import { PushNotifications } from "@capacitor/push-notifications";
import { Capacitor } from "@capacitor/core";
import { getAuth } from "firebase/auth";
import { getFirestore, doc, setDoc } from "firebase/firestore";

const auth = getAuth();
const db = getFirestore();

export const initPushNotifications = async () => {
  if (Capacitor.getPlatform() === "web") {
    console.log("🔔 Notifications push non disponibles sur web");
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
      console.log("🔥 FCM Token :", token.value);
      const user = auth.currentUser;
      if (user) {
        await setDoc(doc(db, "users", user.uid), {
          email: user.email,
          fcmToken: token.value,
        });
        console.log("Token FCM enregistré dans Firestore");
      }
    });

    PushNotifications.addListener("pushNotificationReceived", (notification) => {
      console.log("📩 Notification reçue :", notification);
    });

    PushNotifications.addListener(
      "pushNotificationActionPerformed",
      (notification) => {
        console.log("📩 Action notification :", notification);
      }
    );

    console.log("✅ Notifications push initialisées");
  } catch (err) {
    console.error("Erreur initPushNotifications :", err);
  }
};


// import { PushNotifications } from "@capacitor/push-notifications";
// import { getAuth } from "firebase/auth";
// import { getFirestore, doc, setDoc } from "firebase/firestore";

// // Firebase
// const auth = getAuth();
// const db = getFirestore();

// /**
//  * Initialisation des notifications push
//  */
// export const initPushNotifications = async () => {
//   try {
//     // Demande permission
//     const perm = await PushNotifications.requestPermissions();
//     if (perm.receive !== "granted") {
//       console.log("Permission notifications refusée");
//       return;
//     }

//     // Enregistrement auprès du FCM
//     await PushNotifications.register();

//     // Événement : token reçu
//     PushNotifications.addListener("registration", async (token) => {
//       console.log("🔥 FCM Token :", token.value);

//       // Sauvegarder le token dans Firestore pour l'utilisateur connecté
//       const user = auth.currentUser;
//       if (user) {
//         await setDoc(doc(db, "users", user.uid), {
//           email: user.email,
//           fcmToken: token.value,
//         });
//         console.log("Token FCM enregistré dans Firestore");
//       }
//     });

//     // Événement : notification reçue (quand app ouverte)
//     PushNotifications.addListener("pushNotificationReceived", (notification) => {
//       console.log("📩 Notification reçue :", notification);
//     });

//     // Événement : notification tapée (app ouverte via notification)
//     PushNotifications.addListener(
//       "pushNotificationActionPerformed",
//       (notification) => {
//         console.log("📩 Action notification :", notification);
//       }
//     );

//     console.log("✅ Notifications push initialisées");
//   } catch (err) {
//     console.error("Erreur initPushNotifications :", err);
//   }
// };
