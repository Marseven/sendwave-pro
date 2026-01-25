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
        Schema::create('period_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('period_key', 7); // 2026-01
            $table->integer('total_sms')->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->json('breakdown_by_subaccount')->nullable();
            $table->json('breakdown_by_operator')->nullable();
            $table->json('breakdown_by_type')->nullable();
            $table->enum('status', ['pending', 'closed', 'adjusted'])->default('pending');
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_key']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_closures');
    }
};
