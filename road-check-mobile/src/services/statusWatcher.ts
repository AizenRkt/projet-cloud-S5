import { collection, query, where, onSnapshot, type Unsubscribe } from "firebase/firestore";
import { db, auth } from "@/firebase";
import { Capacitor } from "@capacitor/core";
import { LocalNotifications } from "@capacitor/local-notifications";
import { toastController } from "@ionic/vue";
import { SignalementStatusConfig } from "@/services/signalement/types";

// Cache des statuts précédents pour détecter les changements
const statusCache = new Map<string, string>();
let unsubscribe: Unsubscribe | null = null;
let isFirstLoad = true;

const statusLabels: Record<string, string> = {
  en_attente: "En attente",
  nouveau: "Validé",
  en_cours: "En cours de traitement",
  termine: "Terminé",
  annule: "Annulé",
};

/**
 * Initialise les notifications locales (permissions)
 */
const initLocalNotifications = async () => {
  if (Capacitor.getPlatform() === "web") return;

  try {
    const perm = await LocalNotifications.requestPermissions();
    if (perm.display !== "granted") {
      console.log("Permission notifications locales refusée");
    }
  } catch (err) {
    console.error("Erreur permissions notifications locales:", err);
  }
};

/**
 * Envoie une notification locale
 */
const sendLocalNotification = async (title: string, body: string, id: number) => {
  if (Capacitor.getPlatform() === "web") {
    // Sur web, afficher un toast à la place
    const toast = await toastController.create({
      header: title,
      message: body,
      duration: 5000,
      position: "top",
      color: "primary",
      buttons: [{ text: "OK", role: "cancel" }],
    });
    await toast.present();
    return;
  }

  try {
    await LocalNotifications.schedule({
      notifications: [
        {
          id,
          title,
          body,
          schedule: { at: new Date(Date.now() + 500) },
          sound: "default",
          smallIcon: "ic_notification",
          largeIcon: "ic_launcher",
        },
      ],
    });
  } catch (err) {
    console.error("Erreur envoi notification locale:", err);
    // Fallback: toast
    const toast = await toastController.create({
      header: title,
      message: body,
      duration: 5000,
      position: "top",
      color: "primary",
    });
    await toast.present();
  }
};

/**
 * Démarre la surveillance en temps réel des changements de statut
 * pour les signalements de l'utilisateur connecté
 */
export const startStatusWatcher = () => {
  const user = auth.currentUser;
  if (!user) {
    console.log("StatusWatcher: pas d'utilisateur connecté");
    return;
  }

  // Arrêter une éventuelle écoute précédente
  stopStatusWatcher();

  // Initialiser les notifications locales
  initLocalNotifications();

  // Écouter les signalements de l'utilisateur en temps réel
  const q = query(
    collection(db, "signalements"),
    where("utilisateurId", "==", user.uid)
  );

  isFirstLoad = true;

  unsubscribe = onSnapshot(q, (snapshot) => {
    if (isFirstLoad) {
      // Premier chargement : remplir le cache sans notifier
      snapshot.docs.forEach((doc) => {
        const data = doc.data();
        statusCache.set(doc.id, data.status || "en_attente");
      });
      isFirstLoad = false;
      console.log(`StatusWatcher: ${statusCache.size} signalement(s) en surveillance`);
      return;
    }

    // Vérifier les changements
    snapshot.docChanges().forEach((change) => {
      if (change.type === "modified") {
        const docId = change.doc.id;
        const data = change.doc.data();
        const newStatus = data.status || "en_attente";
        const oldStatus = statusCache.get(docId);

        if (oldStatus && oldStatus !== newStatus) {
          const typeName = data.typeSignalementNom || "Signalement";
          const newLabel = statusLabels[newStatus] || newStatus;

          console.log(`StatusWatcher: ${typeName} ${oldStatus} → ${newStatus}`);

          // Notification locale
          const notifId = Math.floor(Math.random() * 100000);
          sendLocalNotification(
            `${typeName} — ${newLabel}`,
            `Le statut de votre signalement est passé à "${newLabel}".`,
            notifId
          );
        }

        // Mettre à jour le cache
        statusCache.set(docId, newStatus);
      } else if (change.type === "added" && !statusCache.has(change.doc.id)) {
        // Nouveau signalement ajouté (par un autre appareil par ex.)
        statusCache.set(change.doc.id, change.doc.data().status || "en_attente");
      } else if (change.type === "removed") {
        statusCache.delete(change.doc.id);
      }
    });
  }, (error) => {
    console.error("StatusWatcher erreur:", error);
  });

  console.log("StatusWatcher démarré pour", user.email);
};

/**
 * Arrête la surveillance
 */
export const stopStatusWatcher = () => {
  if (unsubscribe) {
    unsubscribe();
    unsubscribe = null;
  }
  statusCache.clear();
  isFirstLoad = true;
  console.log("StatusWatcher arrêté");
};
