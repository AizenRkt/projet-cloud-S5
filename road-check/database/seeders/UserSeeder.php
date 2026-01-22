<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = \App\Models\Role::pluck('id_role', 'nom');

        \App\Models\User::create([
            'email' => 'manager@example.com',
            'firebase_uid' => 'manageruid',
            'nom' => 'Manager',
            'prenom' => 'User',
            'id_role' => $roles['manager'] ?? $roles->last(),
        ]);

        \App\Models\User::create([
            'email' => 'user@example.com',
            'firebase_uid' => 'useruid',
            'nom' => 'Normal',
            'prenom' => 'User',
            'id_role' => $roles['user'] ?? $roles->first(),
        ]);
    }
}
