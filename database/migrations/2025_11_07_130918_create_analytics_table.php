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
        Schema::create('daily_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date')->comment('Date for analytics');
            $table->integer('sms_sent')->default(0)->comment('Total SMS sent');
            $table->integer('sms_delivered')->default(0)->comment('Total SMS delivered');
            $table->integer('sms_failed')->default(0)->comment('Total SMS failed');
            $table->integer('airtel_count')->default(0)->comment('SMS via Airtel');
            $table->integer('moov_count')->default(0)->comment('SMS via Moov');
            $table->decimal('total_cost', 10, 2)->default(0)->comment('Total cost in FCFA');
            $table->integer('campaigns_sent')->default(0)->comment('Campaigns executed');
            $table->integer('contacts_added')->default(0)->comment('New contacts');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_analytics');
    }
};
