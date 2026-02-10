import * as functions from "firebase-functions";
import * as admin from "firebase-admin";

admin.initializeApp();

// Map des labels de statut pour les notifications
const statusLabels: Record<string, string> = {
  en_attente: "En attente",
  nouveau: "Validé",
  en_cours: "En cours de traitement",
  termine: "Terminé",
  annule: "Annulé",
};

// Notification quand le statut d'un signalement change
export const onSignalementStatusChange = functions.firestore
  .document("signalements/{signalementId}")
  .onUpdate(async (change, context) => {
    const before = change.before.data();
    const after = change.after.data();

    // Vérifier si le statut a changé
    if (before.status === after.status) {
      return null;
    }

    const utilisateurId = after.utilisateurId;
    if (!utilisateurId) {
      console.log("Pas d'utilisateur associé au signalement");
      return null;
    }

    // Récupérer le token FCM de l'utilisateur
    const userDoc = await admin.firestore().doc(`users/${utilisateurId}`).get();
    if (!userDoc.exists) {
      console.log(`Utilisateur ${utilisateurId} non trouvé dans Firestore`);
      return null;
    }

    const fcmToken = userDoc.data()?.fcmToken;
    if (!fcmToken) {
      console.log(`Pas de token FCM pour l'utilisateur ${utilisateurId}`);
      return null;
    }

    const newStatusLabel = statusLabels[after.status] || after.status;
    const typeName = after.typeSignalementNom || "Signalement";
    const signalementId = context.params.signalementId;

    const message: admin.messaging.Message = {
      token: fcmToken,
      notification: {
        title: `${typeName} — ${newStatusLabel}`,
        body: `Le statut de votre signalement est passé à "${newStatusLabel}".`,
      },
      data: {
        signalementId: signalementId,
        status: after.status,
        type: "status_change",
      },
      android: {
        notification: {
          channelId: "status_updates",
          priority: "high" as const,
          icon: "ic_notification",
        },
      },
    };

    try {
      await admin.messaging().send(message);
      console.log(
        `Notification de changement de statut envoyée à ${utilisateurId} ` +
        `pour signalement ${signalementId}: ${before.status} → ${after.status}`
      );
    } catch (err: any) {
      // Si le token est invalide, on le supprime
      if (
        err.code === "messaging/invalid-registration-token" ||
        err.code === "messaging/registration-token-not-registered"
      ) {
        console.log(`Token FCM invalide pour ${utilisateurId}, suppression...`);
        await admin.firestore().doc(`users/${utilisateurId}`).update({
          fcmToken: admin.firestore.FieldValue.delete(),
        });
      } else {
        console.error("Erreur envoi notification:", err);
      }
    }

    return null;
  });

// Notification bienvenue après inscription
export const welcomeNotification = functions.auth.user().onCreate(async (user) => {
  const uid = user.uid;

  const doc = await admin.firestore().doc(`users/${uid}`).get();
  if (!doc.exists) return;

  const token = doc.data()?.fcmToken;
  if (!token) return;

  const message = {
    token,
    notification: {
      title: "Bienvenue 👋",
      body: "Merci de vous être inscrit sur l’app !",
    },
  };

  await admin.messaging().send(message);
  console.log(`Notification envoyée à ${user.email}`);
});

// Notification login
export const loginNotification = functions.https.onRequest(async (req, res) => {
  try {
    const { token, email } = req.body;
    if (!token) {
      res.status(400).send("Token FCM manquant");
      return;
    }

    const message = {
      token,
      notification: {
        title: "Bon retour 👋",
        body: "Heureux de vous revoir !",
      },
    };

    await admin.messaging().send(message);
    console.log(`Notification login envoyée à ${email}`);
    res.status(200).send({ success: true });
  } catch (err) {
    console.error("Erreur loginNotification:", err);
    res.status(500).send({ error: err });
  }
});
