<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('utilisateur')->insert([
            'email' => 'admin@gmail.com',
            'password' => Hash::make('manager123'),
            'firebase_uid' => 'manager-default-uid',
            'nom' => 'Admin',
            'prenom' => 'User',
            'id_role' => 1,
            'bloque' => false,
        ]);
    }
}
