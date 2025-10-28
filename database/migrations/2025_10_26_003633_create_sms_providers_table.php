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
        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // msg91, smsala, wapi
            $table->string('name');
            $table->text('api_key')->nullable();
            $table->string('sender_id')->nullable();
            $table->integer('priority')->default(1);
            $table->decimal('cost_per_sms', 8, 4)->default(0.05);
            $table->boolean('is_active')->default(false);
            $table->string('status')->default('disconnected'); // connected, disconnected, error
            $table->json('config')->nullable(); // Pour stocker route, instance_id, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_providers');
    }
};
