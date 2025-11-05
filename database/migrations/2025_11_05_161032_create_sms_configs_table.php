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
        Schema::create('sms_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // 'airtel' ou 'moov'
            $table->string('api_url')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('origin_addr')->nullable(); // Sender ID
            $table->integer('cost_per_sms')->default(20); // Coût en FCFA
            $table->boolean('is_active')->default(false);
            $table->json('additional_config')->nullable(); // Pour configs futures
            $table->timestamps();

            $table->unique('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_configs');
    }
};
