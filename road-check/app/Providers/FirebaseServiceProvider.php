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

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
