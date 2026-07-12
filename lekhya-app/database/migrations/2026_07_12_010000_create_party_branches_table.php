<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An additional GST registration / location of a party (same legal
        // entity, different GSTIN & address). Lets one vendor contact carry
        // several branches instead of duplicating the whole contact.
        Schema::create('party_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('label')->nullable();       // e.g. "Maharashtra Office"
            $table->string('gstin', 15)->nullable()->index();
            $table->string('pan', 10)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('state_code', 2)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Which branch a bill was booked against (null = the party's own GSTIN).
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('party_branch_id')->nullable()->after('party_id')
                ->constrained('party_branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('party_branch_id');
        });
        Schema::dropIfExists('party_branches');
    }
};
