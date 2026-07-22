<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Security foundation:
 *  - token-based team invitations (invitee sets their own password),
 *  - TOTP two-factor auth (Google Authenticator / Authy compatible).
 * Additive & guarded so it is safe to re-run against production MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'invitation_token')) {
                $table->string('invitation_token', 64)->nullable()->index()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'invited_at')) {
                $table->timestamp('invited_at')->nullable()->after('invitation_token');
            }
            if (! Schema::hasColumn('users', 'invited_by')) {
                $table->unsignedBigInteger('invited_by')->nullable()->after('invited_at');
            }
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->after('invited_by');
            }
            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }
            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'invitation_token', 'invited_at', 'invited_by',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
