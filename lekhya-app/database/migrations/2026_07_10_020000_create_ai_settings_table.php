<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->default('lekhya'); // lekhya | anthropic | ollama | mock
            $table->text('api_key')->nullable();          // encrypted at the model layer
            $table->string('text_model')->nullable();     // e.g. llama-3.3-70b-versatile
            $table->string('vision_model')->nullable();   // e.g. meta-llama/llama-4-scout-17b-16e-instruct
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status')->nullable(); // ok | failed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
