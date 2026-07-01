<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name');
            $table->string('app'); // lekhya, seedha_bill
            $table->string('edition'); // standard, pramaan
            $table->string('tier'); // solo, practice, firm
            $table->integer('client_seat_limit')->default(1);
            $table->integer('user_seat_limit')->default(1);
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->default(0);
            $table->boolean('has_trial')->default(true);
            $table->integer('trial_days')->default(14);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['trial', 'active', 'past_due', 'cancelled', 'expired'])->default('trial');
            $table->enum('billing_cycle', ['monthly', 'annual'])->default('monthly');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('razorpay_subscription_id', 100)->nullable();
            $table->string('razorpay_plan_id', 100)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('dunning_count')->default(0);
            $table->timestamp('next_dunning_at')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 30)->unique();
            $table->date('invoice_date');
            $table->decimal('amount', 10, 2);
            $table->decimal('gst_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['draft', 'sent', 'paid', 'failed'])->default('draft');
            $table->string('razorpay_payment_id', 100)->nullable();
            $table->string('razorpay_order_id', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Audit log (append-only)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50)->index(); // invoice.posted, journal.reversed, token.revoked, etc.
            $table->string('auditable_type', 100)->nullable(); // Model class
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tenant_id', 'event_type']);
            $table->index(['auditable_type', 'auditable_id']);
        });

        // AI suggestions (propose-only — never auto-posted)
        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['extraction', 'account_coding', 'reconciliation', 'anomaly', 'nl_query']);
            $table->json('input_context')->nullable();
            $table->json('suggestion')->nullable(); // AI output
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('model_used', 100)->nullable();
            $table->json('model_metadata')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->timestamps();
        });

        // Tally imports
        Schema::create('tally_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('filename');
            $table->string('file_path');
            $table->enum('status', ['uploaded', 'parsing', 'review', 'importing', 'completed', 'failed'])->default('uploaded');
            $table->json('summary')->nullable(); // {masters: 50, vouchers: 1200, errors: 3}
            $table->json('errors')->nullable();
            $table->integer('total_records')->default(0);
            $table->integer('imported_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_imports');
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
