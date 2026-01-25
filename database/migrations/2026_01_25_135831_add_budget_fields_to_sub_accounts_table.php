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
        Schema::table('sub_accounts', function (Blueprint $table) {
            $table->decimal('monthly_budget', 12, 2)->nullable()->after('sms_used');
            $table->decimal('budget_alert_threshold', 5, 2)->default(80)->after('monthly_budget'); // Alerte à 80%
            $table->boolean('block_on_budget_exceeded')->default(false)->after('budget_alert_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_accounts', function (Blueprint $table) {
            $table->dropColumn(['monthly_budget', 'budget_alert_threshold', 'block_on_budget_exceeded']);
        });
    }
};
