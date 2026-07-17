<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settlement support. A `payments` table already exists (scaffolded in the
 * invoice migration) and is referenced by a foreign key, so it can't be dropped
 * — extend it additively: add a TDS column, and add payment_allocations so one
 * receipt/payment can settle several bills. The existing global-unique on
 * reference_number is left in place; the service generates a globally-unique
 * reference (voucher number + journal id) to satisfy it across tenants.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'tds_amount')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('tds_amount', 20, 4)->default(0)->after('amount');
            });
        }

        if (! Schema::hasTable('payment_allocations')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        if (Schema::hasColumn('payments', 'tds_amount')) {
            Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('tds_amount'));
        }
    }
};
