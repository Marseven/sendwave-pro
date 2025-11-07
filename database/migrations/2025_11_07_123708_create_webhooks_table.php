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
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name')->comment('Webhook name');
            $table->string('url')->comment('Target URL');
            $table->string('secret')->nullable()->comment('Secret key for signature verification');
            $table->json('events')->comment('Array of subscribed events');
            $table->boolean('is_active')->default(true);
            $table->integer('retry_limit')->default(3);
            $table->integer('timeout')->default(30)->comment('Timeout in seconds');
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('event')->comment('Event that triggered the webhook');
            $table->json('payload')->comment('Data sent to webhook');
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempt')->default(1);
            $table->boolean('success')->default(false);
            $table->timestamp('triggered_at');
            $table->timestamps();

            $table->index(['webhook_id', 'created_at']);
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
    }
};
