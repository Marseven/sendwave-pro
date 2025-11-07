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
        Schema::table('sub_accounts', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('sub_accounts', 'role')) {
                $table->enum('role', ['admin', 'manager', 'sender', 'viewer'])
                    ->default('sender')
                    ->after('password');
            }

            if (!Schema::hasColumn('sub_accounts', 'sms_credit_limit')) {
                $table->integer('sms_credit_limit')
                    ->nullable()
                    ->comment('NULL = unlimited')
                    ->after('status');
            }

            if (!Schema::hasColumn('sub_accounts', 'sms_used')) {
                $table->integer('sms_used')
                    ->default(0)
                    ->after('sms_credit_limit');
            }

            if (!Schema::hasColumn('sub_accounts', 'permissions')) {
                $table->json('permissions')
                    ->nullable()
                    ->after('sms_used');
            }

            // Update status enum if needed
            DB::statement("ALTER TABLE sub_accounts MODIFY COLUMN status ENUM('active', 'suspended', 'inactive') DEFAULT 'active'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_accounts', function (Blueprint $table) {
            $table->dropColumn(['role', 'sms_credit_limit', 'sms_used', 'permissions']);
        });
    }
};
