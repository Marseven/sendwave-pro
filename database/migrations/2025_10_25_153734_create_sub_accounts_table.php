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
        Schema::create('sub_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'manager', 'sender', 'viewer'])->default('sender');
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->integer('sms_credit_limit')->nullable()->comment('NULL = unlimited');
            $table->integer('sms_used')->default(0);
            $table->json('permissions')->nullable();
            $table->timestamp('last_connection')->nullable();
            $table->timestamps();

            $table->index('parent_user_id');
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_accounts');
    }
};
