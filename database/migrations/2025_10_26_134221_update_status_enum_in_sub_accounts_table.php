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
        // First, update existing data from French to English
        DB::statement("UPDATE sub_accounts SET status = 'active' WHERE status = 'Actif'");
        DB::statement("UPDATE sub_accounts SET status = 'inactive' WHERE status = 'Inactif'");

        // Then, modify the column enum values
        DB::statement("ALTER TABLE sub_accounts MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: update data from English to French
        DB::statement("UPDATE sub_accounts SET status = 'Actif' WHERE status = 'active'");
        DB::statement("UPDATE sub_accounts SET status = 'Inactif' WHERE status = 'inactive'");

        // Restore original enum values
        DB::statement("ALTER TABLE sub_accounts MODIFY COLUMN status ENUM('Actif', 'Inactif') NOT NULL DEFAULT 'Actif'");
    }
};
