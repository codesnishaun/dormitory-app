<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_pending_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('source_message_id')
                ->unique()
                ->constrained('chat_messages')
                ->cascadeOnDelete();
            $table->foreignId('confirmation_message_id')
                ->nullable()
                ->unique()
                ->constrained('chat_messages')
                ->nullOnDelete();
            $table->string('action_type', 40);
            $table->json('payload');
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'chat_conversation_id', 'status', 'expires_at'], 'chat_actions_owner_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_pending_actions');
    }
};
