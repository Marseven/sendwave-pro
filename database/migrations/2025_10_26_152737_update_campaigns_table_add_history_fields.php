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
            // Mettre à jour l'enum status pour inclure completed, scheduled, cancelled, failed
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('completed', 'scheduled', 'cancelled', 'failed', 'active') DEFAULT 'scheduled'");

            // Ajouter les champs manquants
            $table->integer('recipients_count')->default(0)->after('messages_sent');
            $table->integer('sms_count')->default(0)->after('recipients_count');
            $table->integer('cost')->default(0)->after('sms_count'); // Coût en XAF
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');

            // Renommer message_content en message pour cohérence avec le frontend
            $table->renameColumn('message_content', 'message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['recipients_count', 'sms_count', 'cost', 'sent_at']);
            $table->renameColumn('message', 'message_content');
            DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('Actif', 'Terminé', 'Planifié') DEFAULT 'Planifié'");
        });
    }
};
