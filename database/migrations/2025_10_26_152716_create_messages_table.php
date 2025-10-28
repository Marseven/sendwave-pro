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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone');
            $table->text('content');
            $table->enum('type', ['immediate', 'campaign'])->default('immediate');
            $table->enum('status', ['delivered', 'pending', 'failed'])->default('pending');
            $table->string('provider')->nullable(); // msg91, smsala, whapi
            $table->integer('cost')->default(0); // Coût en XAF
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->json('provider_response')->nullable();
            $table->timestamps();

            // Index pour améliorer les performances des requêtes
            $table->index(['user_id', 'sent_at']);
            $table->index(['status', 'sent_at']);
            $table->index(['campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
