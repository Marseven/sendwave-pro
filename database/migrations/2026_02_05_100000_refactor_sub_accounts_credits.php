<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id')->nullable()->after('parent_user_id');
            $table->boolean('is_default')->default(false)->after('status');
            $table->decimal('sms_credits', 12, 2)->default(0)->after('is_default');
            $table->decimal('budget_used', 12, 2)->default(0)->after('sms_credits');

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->index(['account_id', 'is_default']);
        });

        // Migrate existing data: link SubAccount to Account via parent_user_id
        DB::statement("
            UPDATE sub_accounts sa
            INNER JOIN users u ON u.id = sa.parent_user_id
            SET sa.account_id = u.account_id
            WHERE u.account_id IS NOT NULL
        ");

        // Convert existing credits: sms_credits = max(0, sms_credit_limit - sms_used) * 20 (FCFA)
        DB::statement("
            UPDATE sub_accounts
            SET sms_credits = GREATEST(0, COALESCE(sms_credit_limit, 0) - sms_used) * 20
            WHERE sms_credit_limit IS NOT NULL
        ");

        // Create default SubAccount for each Account that doesn't have one
        $accounts = DB::table('accounts')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sub_accounts')
                    ->whereColumn('sub_accounts.account_id', 'accounts.id')
                    ->where('sub_accounts.is_default', true);
            })
            ->get();

        foreach ($accounts as $account) {
            // Find the admin user for this account
            $adminUser = DB::table('users')
                ->where('account_id', $account->id)
                ->where('role', 'admin')
                ->first();

            if (!$adminUser) {
                $adminUser = DB::table('users')
                    ->where('account_id', $account->id)
                    ->first();
            }

            if ($adminUser) {
                DB::table('sub_accounts')->insert([
                    'parent_user_id' => $adminUser->id,
                    'account_id' => $account->id,
                    'name' => 'Compte principal',
                    'email' => "default.{$account->id}@internal.sendwave",
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                    'role' => 'admin',
                    'status' => 'active',
                    'is_default' => true,
                    'sms_credits' => $account->sms_credits ?? 0,
                    'budget_used' => 0,
                    'sms_credit_limit' => null,
                    'sms_used' => 0,
                    'permissions' => json_encode([
                        'send_sms', 'view_history', 'manage_contacts', 'manage_groups',
                        'create_campaigns', 'view_analytics', 'manage_templates', 'export_data',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove default sub-accounts created by this migration
        DB::table('sub_accounts')
            ->where('is_default', true)
            ->where('email', 'LIKE', 'default.%@internal.sendwave')
            ->delete();

        Schema::table('sub_accounts', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropIndex(['account_id', 'is_default']);
            $table->dropColumn(['account_id', 'is_default', 'sms_credits', 'budget_used']);
        });
    }
};
