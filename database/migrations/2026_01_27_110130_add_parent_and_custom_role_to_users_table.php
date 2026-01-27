<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add parent_id if not exists
        if (!Schema::hasColumn('users', 'parent_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('parent_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
            });
        }

        // Add custom_role_id if not exists
        if (!Schema::hasColumn('users', 'custom_role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('custom_role_id')->nullable()->after('role')->constrained('custom_roles')->onDelete('set null');
            });
        }

        // Add status if not exists
        if (!Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('status', ['active', 'suspended', 'pending'])->default('active')->after('permissions');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'custom_role_id')) {
                $table->dropForeign(['custom_role_id']);
                $table->dropColumn('custom_role_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
