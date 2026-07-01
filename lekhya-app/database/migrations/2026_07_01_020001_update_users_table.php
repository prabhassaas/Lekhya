<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone', 15)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->string('avatar_path')->nullable()->after('is_active');
            $table->json('preferences')->nullable()->after('avatar_path');
            $table->timestamp('last_login_at')->nullable()->after('preferences');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tenant_id', 'phone', 'is_active', 'avatar_path', 'preferences', 'last_login_at']);
        });
    }
};
