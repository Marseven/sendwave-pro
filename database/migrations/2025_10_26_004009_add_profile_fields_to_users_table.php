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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('company')->nullable()->after('phone');
            $table->boolean('email_notifications')->default(true)->after('avatar');
            $table->boolean('weekly_reports')->default(true)->after('email_notifications');
            $table->boolean('campaign_alerts')->default(true)->after('weekly_reports');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'company', 'email_notifications', 'weekly_reports', 'campaign_alerts']);
        });
    }
};
