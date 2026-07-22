<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales cycle — recurring invoices. A schedule holds a snapshot of an invoice
 * (party + computed lines + totals) plus a cadence; a daily job raises a fresh
 * draft invoice each period so retainers / rent / AMC bill themselves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->foreignId('party_branch_id')->nullable()->constrained('party_branches')->nullOnDelete();
            $table->string('type', 20)->default('sales');
            $table->string('document_type', 30)->default('tax_invoice');
            $table->enum('frequency', ['weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->unsignedSmallInteger('interval_count')->default(1); // every N periods
            $table->date('start_date');
            $table->date('next_run_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('occurrences_limit')->nullable(); // stop after N invoices
            $table->unsignedInteger('occurrences_generated')->default(0);
            $table->enum('status', ['active', 'paused', 'ended'])->default('active');
            $table->boolean('price_includes_gst')->default(false);
            $table->decimal('tds_rate', 5, 2)->nullable();
            $table->boolean('auto_post')->default(false); // post to ledger automatically
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->json('header')->nullable(); // snapshot: totals / GST split
            $table->json('lines')->nullable();  // snapshot: computed line rows
            $table->foreignId('last_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('last_generated_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'next_run_date']); // driver for the daily job
        });

        // Trace each generated invoice back to its schedule.
        if (! Schema::hasColumn('invoices', 'recurring_invoice_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('recurring_invoice_id')->nullable()->after('converted_from_id');
                $table->index('recurring_invoice_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'recurring_invoice_id')) {
            Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('recurring_invoice_id'));
        }
        Schema::dropIfExists('recurring_invoices');
    }
};
