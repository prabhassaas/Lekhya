<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('state_code', 2)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('country', 50)->default('India');
            $table->string('logo_path')->nullable();
            $table->string('letterhead_path')->nullable();
            $table->string('fiscal_year_start', 5)->default('04-01'); // MM-DD
            $table->string('currency', 3)->default('INR');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('app')->index(); // lekhya, seedha_bill
            $table->string('edition'); // standard, pramaan
            $table->string('plan')->nullable(); // solo, practice, firm
            $table->integer('client_seat_limit')->default(1);
            $table->integer('client_seats_used')->default(0);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlements');
        Schema::dropIfExists('tenants');
    }
};
