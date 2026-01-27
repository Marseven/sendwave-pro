<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('role');
        });

        // Update existing users: convert old role values to new enum values
        // Old 'User' or 'Admin' roles become 'admin' (account owner)
        DB::table('users')->whereIn('role', ['User', 'Admin', 'admin'])->update([
            'role' => UserRole::ADMIN->value,
            'permissions' => json_encode(UserRole::ADMIN->defaultPermissions()),
        ]);

        // Set super_admin permissions for any existing super_admin
        DB::table('users')->where('role', 'super_admin')->update([
            'permissions' => json_encode(UserRole::SUPER_ADMIN->defaultPermissions()),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};
