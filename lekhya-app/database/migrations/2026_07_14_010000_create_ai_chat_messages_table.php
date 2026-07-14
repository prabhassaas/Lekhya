<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Persistent transcript for the in-app assistant — one row per turn (user or
    // AI). Lets a user reopen the chat and see their history, and links a scanned
    // bill back to the AiSuggestion it produced for the "Review & post" hand-off.
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 10);              // user | ai
            $table->text('body');
            $table->string('module', 60)->nullable();
            $table->string('kind', 20)->default('chat');  // chat | scan
            $table->foreignId('suggestion_id')->nullable()->constrained('ai_suggestions')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
