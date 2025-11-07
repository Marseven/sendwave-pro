<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->json('variables')->nullable()->after('content');
            $table->boolean('is_public')->default(false)->after('variables');
            $table->integer('usage_count')->default(0)->after('is_public');

            $table->index('category');
            $table->index('is_public');
            $table->index('usage_count');
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['is_public']);
            $table->dropIndex(['usage_count']);
            $table->dropColumn(['variables', 'is_public', 'usage_count']);
        });
    }
};
