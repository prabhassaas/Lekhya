<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant GST connection. The GSP contract is central (app secrets); each
 * company plugs its own GSTIN + government API credentials in here (encrypted),
 * so e-Invoice / e-Way / returns run under that company's own identity.
 * gst_filings meters each transactional call for billing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gst_settings')) {
            Schema::create('gst_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('gstin', 15)->nullable();
                $table->string('gsp', 30)->nullable();                 // per-tenant GSP override, else central
                $table->string('environment', 12)->default('sandbox'); // sandbox | production
                $table->string('einvoice_username')->nullable();
                $table->text('einvoice_password')->nullable();         // encrypted
                $table->string('ewb_username')->nullable();
                $table->text('ewb_password')->nullable();              // encrypted
                $table->string('returns_username')->nullable();        // GSTN portal user (returns filing)
                $table->string('status', 16)->default('disconnected'); // connected | disconnected
                $table->timestamp('connected_at')->nullable();
                $table->timestamp('last_verified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('gst_filings')) {
            Schema::create('gst_filings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type', 20);                 // irn | cancel_irn | eway | gstr1 | gstr2b
                $table->string('gstin', 15)->nullable();
                $table->string('reference', 100)->nullable(); // invoice no / return period / IRN
                $table->string('status', 12)->default('success'); // success | failed | mock
                $table->string('environment', 12)->default('sandbox');
                $table->boolean('billable')->default(true);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'type']);
                $table->index(['tenant_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gst_filings');
        Schema::dropIfExists('gst_settings');
    }
};
