<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A per-tenant product/service memory learned from invoices: once a line
        // item has been booked with an HSN/SAC + GST rate, remember it so future
        // scans of the same product auto-fill those fields.
        Schema::create('tenant_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');                 // as-seen description
            $table->string('name_key');             // normalized key for matching
            $table->string('hsn_sac', 15)->nullable();
            $table->decimal('gst_rate', 5, 2)->nullable();
            $table->string('unit', 20)->nullable();
            $table->unsignedInteger('times_seen')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name_key']);
            $table->index(['tenant_id', 'hsn_sac']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_items');
    }
};
