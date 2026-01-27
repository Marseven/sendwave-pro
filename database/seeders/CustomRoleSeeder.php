<?php

namespace Database\Seeders;

use App\Models\CustomRole;
use App\Enums\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the custom_roles table first
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CustomRole::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Manager role - Can do most things except settings
        CustomRole::firstOrCreate(
            ['name' => 'Manager'],
            [
                'description' => 'Gestionnaire avec accès étendu aux opérations courantes',
                'permissions' => [
                    Permission::SEND_SMS->value,
                    Permission::VIEW_HISTORY->value,
                    Permission::MANAGE_CONTACTS->value,
                    Permission::MANAGE_GROUPS->value,
                    Permission::CREATE_CAMPAIGNS->value,
                    Permission::VIEW_ANALYTICS->value,
                    Permission::MANAGE_TEMPLATES->value,
                    Permission::EXPORT_DATA->value,
                    Permission::VIEW_AUDIT_LOGS->value,
                ],
                'is_system' => true,
            ]
        );

        // Operator role - Basic operations only
        CustomRole::firstOrCreate(
            ['name' => 'Opérateur'],
            [
                'description' => 'Opérateur limité aux envois SMS et consultation',
                'permissions' => [
                    Permission::SEND_SMS->value,
                    Permission::VIEW_HISTORY->value,
                    Permission::VIEW_ANALYTICS->value,
                ],
                'is_system' => true,
            ]
        );

        // Marketing role - Focus on campaigns and contacts
        CustomRole::firstOrCreate(
            ['name' => 'Marketing'],
            [
                'description' => 'Responsable marketing - Campagnes et contacts',
                'permissions' => [
                    Permission::SEND_SMS->value,
                    Permission::VIEW_HISTORY->value,
                    Permission::MANAGE_CONTACTS->value,
                    Permission::MANAGE_GROUPS->value,
                    Permission::CREATE_CAMPAIGNS->value,
                    Permission::VIEW_ANALYTICS->value,
                    Permission::MANAGE_TEMPLATES->value,
                ],
                'is_system' => true,
            ]
        );

        // Viewer role - Read-only access
        CustomRole::firstOrCreate(
            ['name' => 'Lecteur'],
            [
                'description' => 'Accès en lecture seule aux statistiques et historiques',
                'permissions' => [
                    Permission::VIEW_HISTORY->value,
                    Permission::VIEW_ANALYTICS->value,
                ],
                'is_system' => true,
            ]
        );

        $this->command->info('Custom roles seeded: Manager, Opérateur, Marketing, Lecteur');
    }
}
