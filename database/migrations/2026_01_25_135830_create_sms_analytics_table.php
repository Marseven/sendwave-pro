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
        Schema::create('sms_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sub_account_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('campaign_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('api_key_id')->nullable();
            $table->string('country_code', 3)->default('GA');
            $table->string('operator')->nullable(); // airtel, moov
            $table->string('gateway')->nullable();  // airtel_http, moov_smpp
            $table->string('message_type')->default('transactional'); // transactional, marketing
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->integer('sms_parts')->default(1);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->string('status'); // sent, delivered, failed
            $table->string('period_key', 7); // 2026-01 (pour clôture)
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'period_key']);
            $table->index(['sub_account_id', 'period_key']);
            $table->index(['status', 'period_key']);
            $table->index('is_closed');

            $table->foreign('api_key_id')->references('id')->on('api_keys')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_analytics');
    }
};
