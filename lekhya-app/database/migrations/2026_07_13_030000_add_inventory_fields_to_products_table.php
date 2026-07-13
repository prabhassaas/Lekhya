<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The products table already exists (from the invoice-tables migration) but was
 * never surfaced. This adds the fields the Inventory module needs, guarded so it
 * runs cleanly whatever columns are already present.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'sku'))            $table->string('sku', 60)->nullable()->after('name');
            if (! Schema::hasColumn('products', 'dimension'))      $table->string('dimension')->nullable()->after('type');
            if (! Schema::hasColumn('products', 'quality'))        $table->string('quality')->nullable()->after('dimension');
            if (! Schema::hasColumn('products', 'hsn_sac_code'))   $table->string('hsn_sac_code', 15)->nullable()->after('unit');
            if (! Schema::hasColumn('products', 'gst_rate'))       $table->decimal('gst_rate', 5, 2)->nullable()->after('hsn_sac_code');
            if (! Schema::hasColumn('products', 'sale_price'))     $table->decimal('sale_price', 14, 2)->nullable();
            if (! Schema::hasColumn('products', 'purchase_price')) $table->decimal('purchase_price', 14, 2)->nullable();
            if (! Schema::hasColumn('products', 'track_inventory')) $table->boolean('track_inventory')->default(false);
            if (! Schema::hasColumn('products', 'opening_stock'))  $table->decimal('opening_stock', 14, 3)->default(0);
            if (! Schema::hasColumn('products', 'current_stock'))  $table->decimal('current_stock', 14, 3)->default(0);
            if (! Schema::hasColumn('products', 'deleted_at'))     $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['sku', 'dimension', 'quality', 'hsn_sac_code', 'gst_rate', 'sale_price', 'purchase_price', 'track_inventory', 'opening_stock', 'current_stock'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('products', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
