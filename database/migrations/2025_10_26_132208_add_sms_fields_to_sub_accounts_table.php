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
            $table->integer('credits_remaining')->default(0)->after('status');
            $table->integer('credits_used_this_month')->default(0)->after('credits_remaining');
            $table->decimal('delivery_rate', 5, 2)->default(0)->after('credits_used_this_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_accounts', function (Blueprint $table) {
            $table->dropColumn(['credits_remaining', 'credits_used_this_month', 'delivery_rate']);
        });
    }
};
