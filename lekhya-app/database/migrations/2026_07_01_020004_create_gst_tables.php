<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gstr1_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('return_period', 6); // MMYYYY
            $table->string('gstin', 15);
            $table->enum('status', ['draft', 'filed', 'nil'])->default('draft');
            $table->json('b2b_data')->nullable();
            $table->json('b2c_data')->nullable();
            $table->json('cdnr_data')->nullable();
            $table->json('export_data')->nullable();
            $table->decimal('total_taxable', 20, 4)->default(0);
            $table->decimal('total_igst', 20, 4)->default(0);
            $table->decimal('total_cgst', 20, 4)->default(0);
            $table->decimal('total_sgst', 20, 4)->default(0);
            $table->timestamp('filed_at')->nullable();
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('gstr3b_filings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('return_period', 6);
            $table->string('gstin', 15);
            $table->enum('status', ['draft', 'filed', 'nil'])->default('draft');
            $table->json('outward_supplies')->nullable();
            $table->json('inward_supplies')->nullable();
            $table->json('itc_details')->nullable();
            $table->decimal('net_tax_payable', 20, 4)->default(0);
            $table->timestamp('filed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gstr2b_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('return_period', 6);
            $table->string('gstin', 15);
            $table->json('data')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('gstr2b_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('return_period', 6);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('gstin_supplier', 15);
            $table->string('supplier_invoice_number', 50)->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('invoice_value', 20, 4)->nullable();
            $table->decimal('igst', 20, 4)->default(0);
            $table->decimal('cgst', 20, 4)->default(0);
            $table->decimal('sgst', 20, 4)->default(0);
            $table->enum('status', ['matched', 'mismatch', 'missing_in_2b', 'missing_in_books'])->default('missing_in_2b');
            $table->json('mismatch_details')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_number', 30);
            $table->string('ifsc_code', 11)->nullable();
            $table->string('branch')->nullable();
            $table->enum('account_type', ['savings', 'current', 'cash'])->default('current');
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('description');
            $table->string('reference', 100)->nullable();
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('balance', 20, 4)->default(0);
            $table->enum('status', ['unreconciled', 'reconciled', 'excluded'])->default('unreconciled');
            $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            $table->string('source', 30)->default('manual'); // manual, csv_upload, api
            $table->timestamps();
            $table->index(['tenant_id', 'bank_account_id', 'status']);
        });

        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('statement_date');
            $table->decimal('statement_balance', 20, 4);
            $table->decimal('book_balance', 20, 4);
            $table->decimal('difference', 20, 4)->default(0);
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('gstr2b_reconciliations');
        Schema::dropIfExists('gstr2b_imports');
        Schema::dropIfExists('gstr3b_filings');
        Schema::dropIfExists('gstr1_filings');
    }
};
