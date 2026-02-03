// src/firebase.ts
import { initializeApp } from "firebase/app";
import { getAuth } from "firebase/auth";
import { getFirestore } from "firebase/firestore";

// 🔑 Remplace ces valeurs par celles de ton projet Firebase Web
const firebaseConfig = {
  apiKey: "AIzaSyC7XeriIaw-HtnK6NYM7bAgaut6rjltTqo",
  authDomain: "road-check-a6a4a.firebaseapp.com",
  projectId: "road-check-a6a4a",
  storageBucket: "road-check-a6a4a.firebasestorage.app",
  messagingSenderId: "898288281421",
  appId: "1:898288281421:web:68e264ba05d0b319da7904",
  measurementId: "G-Y0860E5607"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export const db = getFirestore(app);
export default app;

// {
//   "type": "service_account",
//   "project_id": "road-check-a6a4a",
//   "private_key_id": "ae2a684bc4db145b61d5bda63098747dd8f99568",
//   "private_key": "-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDEn2h3QpvTLhsZ\ns3+sHH421oMQyJz1R72FpncAHf2Uze7dSM9IThERKRzYajmtNuy7Xg6fKFdBNg4i\nDkk0nAIVIr55JuzJa1Y/wgpRiotwqCWrITtQVGaRBWsbVnpPDawmKAdPX9TN590v\n3JfZ3xu9hZWXrI9IaSBNJROVR29GelsD50zVfvnuOW2kA+QNN0d5VMyezWQWEUDD\nsBdbOl12D60pTg2uOclfyVlrLnWRTa+oPLXQmxM+E5/zdu2d5fnVKzfBnUkyTt2D\n0zyKt0EwcPnrbf1qNHsTjY3V/j2onUHn5sx5lJbzBmlBotG6Xnt57Xz4IQ/LObwF\nm4FQzmDNAgMBAAECggEAFZtF1cssR/faUwfyWPW1me2XMAY3l02XokLxc0IYblx1\n6fu5CYNIpVfc+1wq8+GkAI/8r9k1Q21+/peOzFjzcqKjhzkzjou8ydtXGnBM0+eq\nYgSQGcr4jWmoxDOL07mXKAdAjGSlxC29VOfbCP5XNEukbWsxQJ4KxmdzYDVeIUKZ\n8xF6vYVJ4KUltcK9Fw1i4RP0ftiMadbtYEvHfFz1LS8C1jOwSpprHVSRmsixD/w7\nocl3q2nN5tPpW3OF8xHCrmdwRKQ42HvgoE8KSrFJfUF4Ha1Bn3LNFyh1XOZgO4OD\nUez3FPqVfOltdfzCXbN/8gtZjz9qDMeieqLnzYJc8QKBgQDsX6oJ8KZZY+sxeZrQ\nd9FH5qdFBBgmBLiKTq5Ss80t4qktRKkuWwYLMhuzcNm+Xre1oZQHGSZMi2u/3Q0X\nSgIIHRgOhVoOwFbRRjHVGzR0tEB8quxUtQ0oKWfnnWc80rHUJWUgjIN6AyOK3ZfD\nt5RFzLJhC7KedYmfOBkiZqoV8QKBgQDU8szuU+ufJbvN/cPuSx13oAOKr6uSx7Cq\neEWOFZ11B4jNN8dKeIcei9Fbh7MJMrMjhHb9TB1B4Yo2uGMfVDGonGf81+VwL09/\nihhJgXWWLUJLQANr2BKAOsHAue/EocgHqY9rSTUa//lkZ9tNezf9uWm5pEo1tzRP\nqShiytGsnQKBgHObMjncVi/AG8a92Ab/ov7Mg6DQqNLnWi5i3wWZ5M79XYrDWw9N\n+328SyoPFp/yCV2vIgv6s+2Z/t/+yClMzAOV3y8y72HplkySTUsSHCy8ABI5Mo8X\nYdDzt1rjdBueXNUKWRR7RM6G/HoteM2DWuRsgg3Ov9SaXe3ebQSTO7qxAoGADJtz\nVojRYlKxk7h6aRk5XWOz0fokhkQcSXtlYswUzmDr0HqE6fvIxB4y/uBB0UGXKXsm\nMEjZUpUvwZ2ZALnTVtArt+RwSPwEEOD0HyXy+Dklu57GlRPtxHm2DtrzPwj09r8r\nJobnBfoxoagka6nn3rWjdMItQpDeH/k70t1/HGECgYAgFOfnrTsiegHKPjOjyeYN\nV8V6w33+ZiZ6kmmMh/bpbInuOGO5FBfhtnqqCAMfLXiiSGY99y/DBCV+3WeOsZoY\nS77blsDKyEy0c4y8LDtjg0qz3dGXuYYX7QkR/LLAl+XsYVXPkChg1ZgdCkcgL1RG\n/yBTiZltPSp1Jsl/kE2N+w==\n-----END PRIVATE KEY-----\n",
//   "client_email": "firebase-adminsdk-fbsvc@road-check-a6a4a.iam.gserviceaccount.com",
//   "client_id": "116933985823034728020",
//   "auth_uri": "https://accounts.google.com/o/oauth2/auth",
//   "token_uri": "https://oauth2.googleapis.com/token",
//   "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
//   "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40road-check-a6a4a.iam.gserviceaccount.com",
//   "universe_domain": "googleapis.com"
// }

