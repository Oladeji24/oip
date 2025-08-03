<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            [ 'email' => 'oladeji_admin@oip.com' ],
            [
                'name' => 'oladeji_admin',
                'email' => 'oladeji_admin@oip.com',
                'password' => Hash::make('Theway_2500q_$'),
                'role' => 'superadmin',
            ]
        );
    }
}
