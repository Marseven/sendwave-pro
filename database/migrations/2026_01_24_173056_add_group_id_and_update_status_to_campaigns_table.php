<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Add group_id for linking campaigns to contact groups
            $table->foreignId('group_id')->nullable()->after('user_id')
                ->constrained('contact_groups')->nullOnDelete();
        });

        // Update status enum to include all new statuses
        // Note: SQLite doesn't support MODIFY COLUMN, so we check driver first
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status VARCHAR(20) DEFAULT 'draft'");
        }

        // Migrate legacy status values to new ones
        DB::table('campaigns')->where('status', 'Actif')->update(['status' => 'sending']);
        DB::table('campaigns')->where('status', 'Terminé')->update(['status' => 'completed']);
        DB::table('campaigns')->where('status', 'Planifié')->update(['status' => 'scheduled']);
        DB::table('campaigns')->where('status', 'active')->update(['status' => 'sending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
