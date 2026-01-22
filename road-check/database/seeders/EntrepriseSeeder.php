<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EntrepriseSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Entreprise::insert([
            ['nom' => 'InfraWorks', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'RoadCare', 'created_at' => now(), 'updated_at' => now()],
            ['nom' => 'UrbanFix', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
