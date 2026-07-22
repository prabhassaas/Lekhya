<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales cycle: links a document to the one it was converted from
 * (Quotation → Sales Order → Tax Invoice), so the chain is traceable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'converted_from_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('converted_from_id')->nullable()->after('source_invoice_id');
                $table->index('converted_from_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'converted_from_id')) {
            Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('converted_from_id'));
        }
    }
};
