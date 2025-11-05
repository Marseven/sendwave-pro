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
        // Pour MySQL, on ne peut pas modifier directement un ENUM
        // Il faut utiliser des requêtes SQL brutes
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('immediate', 'campaign', 'sms') DEFAULT 'immediate'");
        DB::statement("ALTER TABLE messages MODIFY COLUMN status ENUM('delivered', 'pending', 'failed', 'sent') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('immediate', 'campaign') DEFAULT 'immediate'");
        DB::statement("ALTER TABLE messages MODIFY COLUMN status ENUM('delivered', 'pending', 'failed') DEFAULT 'pending'");
    }
};
