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
            $credentialsPath = base_path(env('FIREBASE_CREDENTIALS', 'storage/firebase/firebase_credentials.json'));

            if (!file_exists($credentialsPath)) {
                throw new \Exception("Fichier de credentials Firebase introuvable : $credentialsPath");
            }

            $factory = (new Factory)
                ->withServiceAccount($credentialsPath);

            return $factory->createAuth();
        });

        $this->app->singleton(Firestore::class, function ($app) {
            try {
                $credentialsPath = base_path(env('FIREBASE_CREDENTIALS', 'storage/firebase/firebase_credentials.json'));

                if (!file_exists($credentialsPath)) {
                    throw new \Exception("Fichier de credentials Firebase introuvable : $credentialsPath");
                }

                $factory = (new Factory)
                    ->withServiceAccount($credentialsPath);

                return $factory->createFirestore();
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas planter l'application
                \Log::error('Erreur initialisation Firestore: ' . $e->getMessage());
                return null; // Retourner null au lieu de planter
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
