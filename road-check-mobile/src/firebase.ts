// src/firebase.ts
import { initializeApp } from "firebase/app";
import { getAuth } from "firebase/auth";
import { getFirestore } from "firebase/firestore";

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

