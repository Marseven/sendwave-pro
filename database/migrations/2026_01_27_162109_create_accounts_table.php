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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de l'entreprise/organisation
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company_id')->nullable(); // SIRET, numéro d'entreprise, etc.
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('BJ');
            $table->string('logo')->nullable();

            // Budget et crédits
            $table->decimal('sms_credits', 12, 2)->default(0); // Crédits SMS disponibles
            $table->decimal('monthly_budget', 12, 2)->nullable(); // Budget mensuel alloué
            $table->decimal('budget_used', 12, 2)->default(0); // Budget utilisé ce mois
            $table->decimal('budget_alert_threshold', 5, 2)->default(80); // Alerte à X% du budget
            $table->boolean('block_on_budget_exceeded')->default(false);

            // Statistiques d'utilisation
            $table->integer('sms_sent_total')->default(0);
            $table->integer('sms_sent_month')->default(0);
            $table->integer('campaigns_count')->default(0);
            $table->integer('contacts_count')->default(0);

            // Statut et paramètres
            $table->enum('status', ['active', 'suspended', 'pending'])->default('active');
            $table->json('settings')->nullable(); // Paramètres personnalisés
            $table->text('notes')->nullable(); // Notes internes (admin only)

            // Dates
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        // Ajouter account_id à la table users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'account_id')) {
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            }
        });

        Schema::dropIfExists('accounts');
    }
};
