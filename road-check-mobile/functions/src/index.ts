import * as functions from "firebase-functions";
import * as admin from "firebase-admin";

admin.initializeApp();

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
