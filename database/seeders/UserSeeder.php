<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create development account
        User::updateOrCreate(
            ['email' => 'dev@hb-ku.com'],
            [
                'name' => 'Developer',
                'email' => 'dev@hb-ku.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Development account created:');
        $this->command->info('Email: dev@hb-ku.com');
        $this->command->info('Password: password');
    }
}
