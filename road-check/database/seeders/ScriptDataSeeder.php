<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScriptDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        \DB::statement('SET session_replication_role = replica;');

        // Truncate tables
        \DB::table('modification_signalement')->truncate();
        \DB::table('photo_signalement')->truncate();
        \DB::table('signalement_status')->truncate();
        \DB::table('signalement')->truncate();
        \DB::table('signalement_type_status')->truncate();
        \DB::table('type_signalement')->truncate();
        \DB::table('entreprise')->truncate();
        \DB::table('tentative_connexion')->truncate();
        \DB::table('session')->truncate();
        \DB::table('utilisateur')->truncate();
        \DB::table('role')->truncate();
        \DB::table('prix_m2')->truncate();

        // Enable foreign key checks
        \DB::statement('SET session_replication_role = origin;');

        // Insert Roles
        \DB::table('role')->insert([
            ['nom' => 'Manager'],
            ['nom' => 'Visiteur'],
            ['nom' => 'Utilisateur']
        ]);

        // Insert Users
        \DB::table('utilisateur')->insert([
            [
                'email' => 'admin@gmail.com',
                'password' => '$2y$12$K.x1h0t1.f5k/e5.g5h/u.y5j/k5l.m5n/o5p.q5r/s5t.u5v', // hash for password123 (placeholder)
                'firebase_uid' => 'manager-default-uid',
                'nom' => 'Admin',
                'prenom' => 'Manager',
                'id_role' => 1,
                'bloque' => false
            ],
            [
                'email' => 'visiteur@example.com',
                'password' => '$2y$12$K.x1h0t1.f5k/e5.g5h/u.y5j/k5l.m5n/o5p.q5r/s5t.u5v',
                'firebase_uid' => 'visiteur-default-uid',
                'nom' => 'Visiteur',
                'prenom' => 'Test',
                'id_role' => 2,
                'bloque' => false
            ]
        ]);

        // Insert Entreprises
        \DB::table('entreprise')->insert([
            ['nom' => 'COLAS Madagascar'],
            ['nom' => 'SOGEA SATOM'],
            ['nom' => 'RAVINALA Roads'],
            ['nom' => 'Travaux Publics SA']
        ]);

        // Insert Types
        \DB::table('type_signalement')->insert([
            ['nom' => 'Nid de poule', 'icon' => 'ellipse-outline'],
            ['nom' => 'Fissure', 'icon' => 'remove-outline'],
            ['nom' => 'Affaissement de chaussée', 'icon' => 'trending-down-outline'],
            ['nom' => 'Route inondée', 'icon' => 'water-outline'],
            ['nom' => 'Obstacle sur la chaussée', 'icon' => 'alert-circle-outline'],
            ['nom' => 'Déformation de la chaussée', 'icon' => 'swap-vertical-outline'],
            ['nom' => 'Trou d\'homme non couvert', 'icon' => 'man-outline'],
            ['nom' => 'Signalisation manquante', 'icon' => 'warning-outline'],
            ['nom' => 'Accident de la route', 'icon' => 'car-crash-outline'],
            ['nom' => 'Débris sur la route', 'icon' => 'trash-outline']
        ]);

        // Insert Statuses
        \DB::table('signalement_type_status')->insert([
            ['code' => 'en_attente', 'libelle' => 'En attente', 'pourcentage' => 0],
            ['code' => 'nouveau', 'libelle' => 'Nouveau', 'pourcentage' => 0],
            ['code' => 'en_cours', 'libelle' => 'En cours', 'pourcentage' => 50],
            ['code' => 'termine', 'libelle' => 'Terminé', 'pourcentage' => 100],
            ['code' => 'annule', 'libelle' => 'Annulé', 'pourcentage' => 0]
        ]);

        // Insert Signalements
        $signalementIds = [];
        $signalementIds[] = \DB::table('signalement')->insertGetId([
            'id_type_signalement' => 1,
            'latitude' => -18.9137,
            'longitude' => 47.5361,
            'description' => 'Nid de poule important avenue de l\'Indépendance',
            'surface_m2' => 15.5,
            'budget' => 2500000,
            'id_entreprise' => 1,
            'date_signalement' => now(),
            'niveau' => 1
        ], 'id_signalement');
        $signalementIds[] = \DB::table('signalement')->insertGetId([
            'id_type_signalement' => 2,
            'latitude' => -18.9100,
            'longitude' => 47.5250,
            'description' => 'Fissure sur la route d\'Ambohijatovo',
            'surface_m2' => 8.2,
            'budget' => 1200000,
            'id_entreprise' => 2,
            'date_signalement' => now(),
            'niveau' => 1
        ], 'id_signalement');
        $signalementIds[] = \DB::table('signalement')->insertGetId([
            'id_type_signalement' => 1,
            'latitude' => -18.9200,
            'longitude' => 47.5400,
            'description' => 'Plusieurs nids de poule à Analakely',
            'surface_m2' => 25.0,
            'budget' => 4500000,
            'id_entreprise' => null,
            'date_signalement' => now(),
            'niveau' => 1
        ], 'id_signalement');
        $signalementIds[] = \DB::table('signalement')->insertGetId([
            'id_type_signalement' => 3,
            'latitude' => -18.9050,
            'longitude' => 47.5300,
            'description' => 'Affaissement près du lac Anosy',
            'surface_m2' => 12.0,
            'budget' => 8000000,
            'id_entreprise' => 3,
            'date_signalement' => now(),
            'niveau' => 1
        ], 'id_signalement');
        $signalementIds[] = \DB::table('signalement')->insertGetId([
            'id_type_signalement' => 4,
            'latitude' => -18.9180,
            'longitude' => 47.5280,
            'description' => 'Route inondée à Isotry',
            'surface_m2' => 50.0,
            'budget' => 15000000,
            'id_entreprise' => 1,
            'date_signalement' => now(),
            'niveau' => 1
        ], 'id_signalement');

        // Insert Signalement Statuses
        $statuses = ['en_attente', 'nouveau', 'en_cours', 'termine', 'annule'];
        foreach ($signalementIds as $index => $id) {
            $code = $statuses[$index % count($statuses)];
            $statusId = \DB::table('signalement_type_status')->where('code', $code)->value('id_signalement_type_status');
            \DB::table('signalement_status')->insert([
                'id_signalement' => $id,
                'id_signalement_type_status' => $statusId,
                'date_modification' => now()
            ]);
        }

        // Insert PrixM2
        \DB::table('prix_m2')->insert([
            'date' => now(),
            'valeur' => 5000,
        ]);
    }
}
