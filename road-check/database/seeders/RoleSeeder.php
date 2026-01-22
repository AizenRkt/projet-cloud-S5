<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('role')->upsert([
            ['nom' => 'visitor'],
            ['nom' => 'user'],
            ['nom' => 'manager'],
        ], ['nom']);
    }
}
