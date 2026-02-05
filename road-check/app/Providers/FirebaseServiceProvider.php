<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Firestore;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Auth::class, function ($app) {
            $credentialsPath = config('services.firebase.credentials', base_path('storage/firebase/firebase_credentials.json'));

            if (!file_exists($credentialsPath)) {
                throw new \Exception("Fichier de credentials Firebase introuvable : $credentialsPath");
            }

            $factory = (new Factory)
                ->withServiceAccount($credentialsPath);

            return $factory->createAuth();
        });

        $this->app->singleton(Firestore::class, function ($app) {
            $credentialsPath = config('services.firebase.credentials', base_path('storage/firebase/firebase_credentials.json'));

            if (!file_exists($credentialsPath)) {
                \Log::warning('Firebase credentials file not found: ' . $credentialsPath);
                return null;
            }

            try {
                $factory = (new Factory)
                    ->withServiceAccount($credentialsPath);

                $firestore = $factory->createFirestore();

                // Test de connexion basique
                $firestore->database()->collection('test')->limit(1)->documents();

                return $firestore;
            } catch (\Exception $e) {
                \Log::error('Erreur initialisation Firestore: ' . $e->getMessage());
                \Log::info('Firestore sera désactivé. Vérifiez que Firestore est activé dans votre projet Firebase.');
                return null;
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
