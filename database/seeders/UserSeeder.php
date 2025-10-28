<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Hervé Ndjibi',
            'email' => 'admin@jobs-sms.com',
            'password' => Hash::make('password123'),
            'role' => 'Admin',
            'avatar' => '/lovable-uploads/b0eed011-e1a3-4c14-b352-4d36872d2778.png'
        ]);
    }
}
