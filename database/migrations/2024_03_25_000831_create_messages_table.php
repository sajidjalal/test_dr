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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Group name (null for 1-to-1)
            $table->boolean('is_group')->default(false); // Single or group chat
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete()->name('fk_chat_participants_chat_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->name('fk_chat_participants_user_id');
            $table->boolean('is_admin')->default(false); // Group admin
            $table->timestamps();
            $table->softDeletes();

            // $table->unique(['chat_id', 'user_id']); // prevent duplicate participants
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete()->name('fk_messages_chat_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->name('fk_messages_user_id')->comment('sender_id');
            $table->text('text')->nullable();
            $table->foreignId('reply_to')->nullable()->constrained('messages')->cascadeOnDelete()->name('fk_messages_reply_to');
            $table->string('attachment')->nullable(); // file path
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('message_receivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete()->name('fk_message_receivers_message_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->name('fk_message_receivers_user_id')->comment('receiver_id');
            $table->tinyInteger('status')->default(0)->comment('0=sent, 1=delivered, 2=read');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
        Schema::dropIfExists('chat_participants');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_receivers');
    }
};
