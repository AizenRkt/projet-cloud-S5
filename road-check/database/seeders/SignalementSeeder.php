<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SignalementSeeder extends Seeder
{
    public function run(): void
    {
        $user = \App\Models\User::first();
        $entreprises = \App\Models\Entreprise::pluck('id_entreprise');
        $entrepriseId = $entreprises->isNotEmpty() ? $entreprises->random() : null;

        \App\Models\Signalement::factory()->count(3)->create([
            'id_utilisateur' => $user?->id_utilisateur,
            'id_entreprise' => $entrepriseId,
        ]);
    }
}
