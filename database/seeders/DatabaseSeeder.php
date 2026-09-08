<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {

        User::firstOrCreate(
            ['email' => 'admin@bpsdmp.go.id'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('admin_BPSDMP26'),
                'role'     => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'internship@bpsdmp.go.id'],
            [
                'name'     => 'Internship',
                'password' => Hash::make('internship_BPSDMP'),
                'role'     => 'internship',
            ]
        );
    }
}
