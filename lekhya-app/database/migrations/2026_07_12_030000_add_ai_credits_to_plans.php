<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Monthly AI request allowance for the plan (invoice scans, NL queries,
        // auto-coding). Null = unlimited / fair-use. Metered via ai_usage.
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('ai_credits')->nullable()->after('user_seat_limit');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('ai_credits');
        });
    }
};
