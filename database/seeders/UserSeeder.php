<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the users table first (disable foreign key checks temporarily)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create SuperAdmin user
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Hervé Ndjibi',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPER_ADMIN->value,
                'permissions' => UserRole::SUPER_ADMIN->defaultPermissions(),
                'status' => 'active',
                'avatar' => '/lovable-uploads/b0eed011-e1a3-4c14-b352-4d36872d2778.png'
            ]
        );

        $this->command->info('SuperAdmin user created: admin@admin.com');
    }
}
