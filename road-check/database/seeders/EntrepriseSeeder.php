<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntrepriseSeeder extends Seeder
{
    public function run()
    {
        DB::table('entreprise')->insert([
            ['nom' => 'Entreprise A'],
            ['nom' => 'Entreprise B'],
            ['nom' => 'Entreprise C'],
        ]);
    }
}
