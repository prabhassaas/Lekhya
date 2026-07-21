<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-company: one user can own/switch between several companies (tenants),
 * each with its own GSTIN and books. Membership lives in company_user; the
 * active company stays on users.tenant_id (so every existing query keeps
 * working). Secondary companies point at the primary via owner_tenant_id and
 * inherit its plan/entitlement, so one subscription covers all of a user's
 * companies up to the plan's limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'owner_tenant_id')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_tenant_id')->nullable()->after('id');
                $table->index('owner_tenant_id');
            });
        }

        if (! Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('role', 20)->default('owner'); // owner | member
                $table->timestamps();
                $table->unique(['user_id', 'tenant_id']);
            });

            // Backfill: every existing user is an owner of their current company.
            foreach (DB::table('users')->whereNotNull('tenant_id')->get(['id', 'tenant_id']) as $u) {
                DB::table('company_user')->insertOrIgnore([
                    'user_id' => $u->id, 'tenant_id' => $u->tenant_id, 'role' => 'owner',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
        if (Schema::hasColumn('tenants', 'owner_tenant_id')) {
            Schema::table('tenants', fn (Blueprint $table) => $table->dropColumn('owner_tenant_id'));
        }
    }
};
