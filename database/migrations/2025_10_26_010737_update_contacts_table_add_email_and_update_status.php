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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->string('status')->default('active')->change();
            $table->string('group')->nullable()->change();
        });

        // Update existing status values
        \DB::table('contacts')->where('status', 'Actif')->update(['status' => 'active']);
        \DB::table('contacts')->where('status', 'Inactif')->update(['status' => 'inactive']);
        \DB::table('contacts')->where('status', 'En Attente')->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
