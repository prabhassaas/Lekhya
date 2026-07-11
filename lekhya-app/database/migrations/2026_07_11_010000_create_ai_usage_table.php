<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Per-tenant AI usage metering — the linchpin for credit limits (billing)
    // and the Prabhas SaaS admin console. One row per AI call.
    public function up(): void
    {
        Schema::create('ai_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);            // extraction | nl_query | account_coding | anomaly
            $table->string('driver', 20)->nullable();
            $table->unsignedInteger('tokens')->default(0);
            $table->boolean('billable')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage');
    }
};
