<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DriveConfig;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'nama'     => 'Admin',
            'email'    => 'admin@sistemdokumen.test',
            'password' => 'admin123',
            'role'     => 'admin',
        ]);

        User::create([
            'username' => 'budi',
            'nama'     => 'Budi Santoso',
            'email'    => 'budi@sistemdokumen.test',
            'password' => 'budi123',
            'role'     => 'user',
        ]);

        DriveConfig::create([
            'folder_id'   => '',
            'folder_link' => '',
        ]);
    }
}
