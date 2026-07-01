<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // HSN/SAC Master
        Schema::create('hsn_sac_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->enum('type', ['hsn', 'sac']);
            $table->text('description');
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('cess_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Products / Services
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['product', 'service']);
            $table->string('unit', 20)->default('NOS');
            $table->foreignId('hsn_sac_id')->nullable()->constrained('hsn_sac_codes')->nullOnDelete();
            $table->decimal('rate', 20, 4)->default(0);
            $table->foreignId('sales_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('purchase_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Invoices (Sales & Purchase)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['sales', 'purchase', 'sales_return', 'purchase_return']);
            $table->string('invoice_number', 30);
            $table->string('reference_number', 50)->nullable(); // supplier's invoice number for purchase
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('place_of_supply', 2)->nullable();
            $table->boolean('is_interstate')->default(false);
            $table->boolean('reverse_charge')->default(false);
            $table->enum('status', ['draft', 'posted', 'partially_paid', 'paid', 'cancelled', 'locked'])->default('draft');
            $table->string('source', 30)->default('manual'); // manual, seedha_bill, import
            $table->string('source_invoice_id', 100)->nullable();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('taxable_amount', 20, 4)->default(0);
            $table->decimal('cgst_amount', 20, 4)->default(0);
            $table->decimal('sgst_amount', 20, 4)->default(0);
            $table->decimal('igst_amount', 20, 4)->default(0);
            $table->decimal('cess_amount', 20, 4)->default(0);
            $table->decimal('total_tax', 20, 4)->default(0);
            $table->decimal('round_off', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('paid_amount', 20, 4)->default(0);
            $table->decimal('balance_amount', 20, 4)->default(0);
            $table->string('irn', 64)->nullable(); // e-invoice IRN
            $table->string('ack_number', 50)->nullable();
            $table->timestamp('ack_date')->nullable();
            $table->text('signed_qr')->nullable();
            $table->string('eway_bill_number', 20)->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'invoice_number', 'type']);
            $table->index(['tenant_id', 'type', 'status']);
            $table->index(['tenant_id', 'party_id']);
        });

        // Invoice Lines
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->integer('line_order')->default(0);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->string('hsn_sac_code', 8)->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->string('unit', 20)->default('NOS');
            $table->decimal('rate', 20, 4)->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('taxable_amount', 20, 4)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 20, 4)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_amount', 20, 4)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('igst_amount', 20, 4)->default(0);
            $table->decimal('cess_rate', 5, 2)->default(0);
            $table->decimal('cess_amount', 20, 4)->default(0);
            $table->decimal('line_total', 20, 4)->default(0);
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();
        });

        // Payments / Receipts
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['receipt', 'payment']);
            $table->string('reference_number', 30)->unique();
            $table->date('date');
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('account_id')->constrained('accounts'); // bank/cash account
            $table->decimal('amount', 20, 4);
            $table->string('payment_mode', 30)->default('bank_transfer'); // cash, cheque, bank_transfer, upi
            $table->string('transaction_reference', 100)->nullable();
            $table->date('cheque_date')->nullable();
            $table->text('narration')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Invoice Payments (allocation)
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_allocated', 20, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('hsn_sac_codes');
    }
};
