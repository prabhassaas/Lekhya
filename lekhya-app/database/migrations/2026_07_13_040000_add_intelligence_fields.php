<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields that let the posting form hold everything the AI extracts, plus
 * GST-inclusive handling, TDS on service providers, party classification and
 * a link back to the original scanned file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'price_includes_gst')) $table->boolean('price_includes_gst')->default(false)->after('reverse_charge');
            if (! Schema::hasColumn('invoices', 'tds_rate'))          $table->decimal('tds_rate', 5, 2)->nullable()->after('cess_amount');
            if (! Schema::hasColumn('invoices', 'tds_amount'))        $table->decimal('tds_amount', 14, 2)->nullable()->after('tds_rate');
            if (! Schema::hasColumn('invoices', 'source_file_path'))  $table->string('source_file_path')->nullable();
            if (! Schema::hasColumn('invoices', 'source_file_name'))  $table->string('source_file_name')->nullable();
            if (! Schema::hasColumn('invoices', 'extra'))             $table->json('extra')->nullable();
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_lines', 'meta')) $table->json('meta')->nullable()->after('line_total');
        });

        Schema::table('parties', function (Blueprint $table) {
            if (! Schema::hasColumn('parties', 'classification')) $table->string('classification', 30)->nullable()->after('type');
            if (! Schema::hasColumn('parties', 'tds_rate'))       $table->decimal('tds_rate', 5, 2)->nullable()->after('upi_id');
            if (! Schema::hasColumn('parties', 'tds_section'))    $table->string('tds_section', 20)->nullable()->after('tds_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            foreach (['price_includes_gst', 'tds_rate', 'tds_amount', 'source_file_path', 'source_file_name', 'extra'] as $c) {
                if (Schema::hasColumn('invoices', $c)) $table->dropColumn($c);
            }
        });
        Schema::table('invoice_lines', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_lines', 'meta')) $table->dropColumn('meta');
        });
        Schema::table('parties', function (Blueprint $table) {
            foreach (['classification', 'tds_rate', 'tds_section'] as $c) {
                if (Schema::hasColumn('parties', $c)) $table->dropColumn($c);
            }
        });
    }
};
