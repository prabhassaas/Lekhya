<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settlement tables. The `payments` table was scaffolded in the invoice
 * migration but never used (0 rows) and carried a GLOBAL unique on
 * reference_number, which would collide across tenants. Recreate it cleanly
 * with a per-tenant unique + a TDS column, and add payment_allocations so one
 * receipt/payment can settle several bills.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10);                       // receipt | payment
            $table->string('reference_number', 40)->nullable();
            $table->date('date');
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('account_id')->constrained('accounts'); // bank / cash ledger
            $table->decimal('amount', 20, 4);                 // gross settled
            $table->decimal('tds_amount', 20, 4)->default(0);
            $table->string('payment_mode', 30)->nullable();   // cash | cheque | bank_transfer | upi
            $table->string('transaction_reference', 100)->nullable();
            $table->text('narration')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'reference_number']);
            $table->index(['tenant_id', 'type', 'date']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 20, 4);
            $table->timestamps();
            $table->index(['tenant_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};
