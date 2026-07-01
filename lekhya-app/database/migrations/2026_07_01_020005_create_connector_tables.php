<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Connector tokens (Mode B: different Prabhas accounts)
        Schema::create('connector_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); // Lekhya accountant tenant
            $table->foreignId('created_by')->constrained('users');
            $table->string('token_hash', 64)->unique(); // sha256 of actual token
            $table->string('label'); // human-readable label
            $table->json('scope')->nullable(); // ['read:invoices']
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        // Established connections (token accepted by a Seedha Bill freelancer)
        Schema::create('connector_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete(); // Lekhya accountant
            $table->foreignId('connector_token_id')->constrained()->cascadeOnDelete();
            $table->string('source_label'); // "Ravi Kumar - Freelancer"
            $table->string('source_external_id')->nullable(); // Seedha Bill tenant/user id
            $table->enum('mode', ['mode_a', 'mode_b'])->default('mode_b');
            $table->enum('status', ['active', 'paused', 'revoked'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_sync_status', 30)->nullable();
            $table->integer('invoices_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // Import queue / pipeline
        Schema::create('connector_import_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('connector_connections')->nullOnDelete();
            $table->string('source', 30)->default('seedha_bill'); // seedha_bill, csv, manual
            $table->string('external_id', 100)->nullable();
            $table->json('raw_payload'); // original invoice data
            $table->json('normalized_payload')->nullable(); // after mapping
            $table->enum('status', ['pending', 'validated', 'quarantined', 'posted', 'duplicate', 'skipped'])->default('pending');
            $table->text('error_details')->nullable();
            $table->json('validation_errors')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'source', 'external_id']);
        });

        // Connector event log
        Schema::create('connector_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('connector_connections')->nullOnDelete();
            $table->foreignId('token_id')->nullable()->constrained('connector_tokens')->nullOnDelete();
            $table->string('event_type', 50); // sync_started, token_generated, token_revoked, invoice_posted, etc.
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 30)->default('user'); // user, system, api
            $table->timestamps();
            $table->index(['tenant_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connector_events');
        Schema::dropIfExists('connector_import_queue');
        Schema::dropIfExists('connector_connections');
        Schema::dropIfExists('connector_tokens');
    }
};
