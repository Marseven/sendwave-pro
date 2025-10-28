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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('status', ['Actif', 'Terminé', 'Planifié'])->default('Planifié');
            $table->integer('messages_sent')->default(0);
            $table->decimal('delivery_rate', 5, 2)->default(0);
            $table->decimal('ctr', 5, 2)->default(0);
            $table->string('sms_provider')->nullable(); // MSG91, SMSALA, WAPI
            $table->text('message_content')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
