<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chart of Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('sub_type', [
                'current_asset', 'fixed_asset', 'current_liability', 'long_term_liability',
                'equity', 'revenue', 'cost_of_sales', 'expense', 'other_income', 'other_expense'
            ])->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->integer('level')->default(1);
            $table->boolean('is_ledger')->default(true); // false = group
            $table->boolean('is_system')->default(false); // system accounts can't be deleted
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->enum('opening_balance_type', ['debit', 'credit'])->default('debit');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'code']);
        });

        // Fiscal Years
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 20); // e.g. "2024-25"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // Journal Vouchers
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->string('voucher_number', 30)->unique();
            $table->enum('voucher_type', [
                'sales', 'purchase', 'receipt', 'payment', 'contra', 'journal',
                'debit_note', 'credit_note', 'opening', 'closing', 'reversal'
            ]);
            $table->date('date');
            $table->text('narration')->nullable();
            $table->string('reference', 100)->nullable();
            $table->decimal('total_debit', 20, 4)->default(0);
            $table->decimal('total_credit', 20, 4)->default(0);
            $table->boolean('is_posted')->default(false);
            $table->boolean('is_reversed')->default(false);
            $table->foreignId('reversed_by_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('reversal_of_journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'voucher_type']);
        });

        // Journal Lines (double-entry)
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->integer('line_order')->default(0);
            $table->decimal('debit', 20, 4)->default(0);
            $table->decimal('credit', 20, 4)->default(0);
            $table->text('narration')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'account_id']);
        });

        // Parties (Customers & Vendors)
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['customer', 'vendor', 'both']);
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('gstin', 15)->nullable()->index();
            $table->string('pan', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('state_code', 2)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->foreignId('receivable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('payable_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parties');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('accounts');
    }
};
