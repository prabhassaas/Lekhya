<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('pincode');
            $table->string('bank_account_number', 34)->nullable()->after('bank_name');
            $table->string('bank_ifsc', 15)->nullable()->after('bank_account_number');
            $table->string('bank_account_holder')->nullable()->after('bank_ifsc');
            $table->string('upi_id')->nullable()->after('bank_account_holder');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_number', 'bank_ifsc', 'bank_account_holder', 'upi_id']);
        });
    }
};
